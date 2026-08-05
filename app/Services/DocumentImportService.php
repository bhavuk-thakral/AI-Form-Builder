<?php

namespace App\Services;

use App\Models\Form;
use App\Models\FormVersion;
use App\Models\ActivityLog;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use PhpOffice\PhpWord\IOFactory as PhpWordIO;
use PhpOffice\PhpSpreadsheet\IOFactory as PhpSpreadsheetIO;

class DocumentImportService
{
    /**
     * Parse and import a form schema from a file (DOCX or XLSX).
     */
    public function importFormFromFile($file, int $userId): Form
    {
        $originalName = $file->getClientOriginalName();
        $extension = strtolower($file->getClientOriginalExtension());
        $filePath = $file->getRealPath();

        $fields = [];

        if ($extension === 'docx') {
            $fields = $this->parseDocx($filePath);
        } elseif ($extension === 'xlsx') {
            $fields = $this->parseXlsx($filePath);
        } else {
            throw new \InvalidArgumentException('Unsupported file format. Please upload a .docx or .xlsx document.');
        }

        if (empty($fields)) {
            throw new \Exception('No fields or sections could be extracted from the uploaded document.');
        }

        return DB::transaction(function () use ($fields, $originalName, $userId) {
            $title = 'Imported Form - ' . pathinfo($originalName, PATHINFO_FILENAME);
            $description = 'Automatically compiled from uploaded document: ' . $originalName;

            $schema = [
                'title' => $title,
                'description' => $description,
                'fields' => $fields
            ];

            // Create form
            $form = Form::create([
                'user_id' => $userId,
                'title' => $title,
                'description' => $description,
                'status' => 'draft',
                'schema' => $schema,
                'share_token' => Str::random(32),
            ]);

            // Save initial version
            FormVersion::create([
                'form_id' => $form->id,
                'version_number' => 1,
                'schema' => $schema,
                'created_by' => $userId,
            ]);

            // Log import action
            ActivityLog::create([
                'user_id' => $userId,
                'form_id' => $form->id,
                'action' => 'imported',
                'description' => "Imported form layout from file '{$originalName}'.",
                'ip_address' => request()->ip() ?? '127.0.0.1',
            ]);

            return $form;
        });
    }

    /**
     * Parse DOCX structure: convert headings to sections and text to fields.
     */
    protected function parseDocx(string $filePath): array
    {
        $phpWord = PhpWordIO::load($filePath);
        $fields = [];

        foreach ($phpWord->getSections() as $section) {
            foreach ($section->getElements() as $element) {
                $text = '';
                $isHeading = false;

                // Extract text from elements
                if ($element instanceof \PhpOffice\PhpWord\Element\Title) {
                    $isHeading = true;
                    if (method_exists($element, 'getText')) {
                        $text = $element->getText();
                    } else {
                        foreach ($element->getElements() as $subElement) {
                            if (method_exists($subElement, 'getText')) {
                                $text .= $subElement->getText();
                            }
                        }
                    }
                } elseif ($element instanceof \PhpOffice\PhpWord\Element\Text) {
                    $text = $element->getText();
                } elseif ($element instanceof \PhpOffice\PhpWord\Element\TextRun) {
                    foreach ($element->getElements() as $subElement) {
                        if (method_exists($subElement, 'getText')) {
                            $text .= $subElement->getText();
                        }
                    }
                }

                $text = trim($text);
                if (empty($text)) {
                    continue;
                }

                // Check for heading status via Word styles
                if (method_exists($element, 'getParagraphStyle')) {
                    $pStyle = $element->getParagraphStyle();
                    if ($pStyle && method_exists($pStyle, 'getStyleName')) {
                        $styleName = $pStyle->getStyleName();
                        if (Str::contains(strtolower($styleName), ['heading', 'title'])) {
                            $isHeading = true;
                        }
                    }
                }

                // Fallback Heading classification checks
                if (!$isHeading) {
                    $lowerText = strtolower($text);
                    if (
                        str_starts_with($lowerText, 'section') || 
                        str_starts_with($lowerText, 'part') || 
                        (strlen($text) < 60 && $text === strtoupper($text))
                    ) {
                        $isHeading = true;
                    }
                }

                if ($isHeading) {
                    $fields[] = [
                        'key' => 'section_' . Str::random(6),
                        'type' => 'section',
                        'label' => $text,
                        'help_text' => 'Imported heading break.',
                    ];
                } else {
                    // Question classification
                    if (Str::endsWith($text, '?') || strlen($text) < 120) {
                        $fieldType = $this->classifyFieldType($text);
                        $key = Str::snake(Str::limit($text, 20, '')) . '_' . Str::random(4);
                        
                        $fields[] = [
                            'key' => $key,
                            'type' => $fieldType,
                            'label' => $text,
                            'placeholder' => $fieldType === 'section' ? '' : 'Enter details...',
                            'help_text' => '',
                            'required' => false,
                            'default_value' => $fieldType === 'rating' ? '0' : '',
                            'validations' => $fieldType === 'email' ? ['email'] : ($fieldType === 'date' ? ['date'] : []),
                            'options' => in_array($fieldType, ['dropdown', 'radio', 'checkbox']) ? ['Option A', 'Option B'] : []
                        ];
                    }
                }
            }
        }

        return $fields;
    }

    /**
     * Parse XLSX columns to form fields.
     */
    protected function parseXlsx(string $filePath): array
    {
        $spreadsheet = PhpSpreadsheetIO::load($filePath);
        $sheet = $spreadsheet->getActiveSheet();
        $fields = [];

        $highestColumn = $sheet->getHighestColumn();
        $highestColumnIndex = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($highestColumn);

        for ($col = 1; $col <= $highestColumnIndex; $col++) {
            $colLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col);
            $header = trim($sheet->getCell($colLetter . '1')->getValue() ?? '');
            if (empty($header)) {
                continue;
            }

            $fieldType = $this->classifyFieldType($header);
            $key = Str::snake($header) . '_' . Str::random(4);

            $fields[] = [
                'key' => $key,
                'type' => $fieldType,
                'label' => $header,
                'placeholder' => 'Enter ' . strtolower($header) . '...',
                'help_text' => 'Imported excel mapping column.',
                'required' => false,
                'default_value' => $fieldType === 'rating' ? '0' : '',
                'validations' => $fieldType === 'email' ? ['email'] : ($fieldType === 'date' ? ['date'] : []),
                'options' => in_array($fieldType, ['dropdown', 'radio', 'checkbox']) ? ['Option A', 'Option B'] : []
            ];
        }

        return $fields;
    }

    /**
     * Classify HTML field input types based on label keywords.
     */
    protected function classifyFieldType(string $label): string
    {
        $labelLower = strtolower($label);

        if (str_contains($labelLower, 'name')) {
            return 'text';
        }
        if (str_contains($labelLower, 'email')) {
            return 'email';
        }
        if (str_contains($labelLower, 'phone') || str_contains($labelLower, 'mobile') || str_contains($labelLower, 'tel')) {
            return 'phone';
        }
        if (str_contains($labelLower, 'date') || str_contains($labelLower, 'birthday') || str_contains($labelLower, 'dob')) {
            return 'date';
        }
        if (str_contains($labelLower, 'resume') || str_contains($labelLower, 'cv') || str_contains($labelLower, 'upload') || str_contains($labelLower, 'file')) {
            return 'file';
        }
        if (str_contains($labelLower, 'rating') || str_contains($labelLower, 'rate') || str_contains($labelLower, 'score')) {
            return 'rating';
        }
        if (str_contains($labelLower, 'comments') || str_contains($labelLower, 'feedback') || str_contains($labelLower, 'message') || str_contains($labelLower, 'description')) {
            return 'textarea';
        }
        if (str_contains($labelLower, 'gender') || str_contains($labelLower, 'status')) {
            return 'radio';
        }
        if (str_contains($labelLower, 'hobbies') || str_contains($labelLower, 'skills') || str_contains($labelLower, 'interests')) {
            return 'checkbox';
        }
        if (str_contains($labelLower, 'country') || str_contains($labelLower, 'role') || str_contains($labelLower, 'select')) {
            return 'dropdown';
        }
        if (str_contains($labelLower, 'age') || str_contains($labelLower, 'salary') || str_contains($labelLower, 'experience')) {
            return 'number';
        }

        return 'text';
    }
}
