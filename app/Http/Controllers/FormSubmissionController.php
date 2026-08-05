<?php

namespace App\Http\Controllers;

use App\Models\Form;
use App\Models\Submission;
use App\Services\SubmissionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class FormSubmissionController extends Controller
{
    protected $submissionService;

    /**
     * Inject SubmissionService.
     */
    public function __construct(SubmissionService $submissionService)
    {
        $this->submissionService = $submissionService;
    }

    /**
     * Display a list of submissions for a specific form.
     */
    public function index(Request $request, Form $form)
    {
        // Enforce form ownership
        if ($form->user_id !== Auth::id()) {
            abort(403, 'Unauthorized action.');
        }

        $search = $request->query('search');
        $submissions = $this->submissionService->getSubmissionsForForm($form, $search, 10);

        return view('forms.submissions', compact('form', 'submissions', 'search'));
    }

    /**
     * Get detailed answers for a single submission (JSON endpoint for modals).
     */
    public function show(Submission $submission)
    {
        // Enforce form ownership check via relation
        if ($submission->form->user_id !== Auth::id()) {
            abort(403, 'Unauthorized action.');
        }

        // Load all answers
        $answers = $submission->answers;
        
        // Map answers with their labels from the form schema for convenience
        $fields = $submission->form->schema['fields'] ?? [];
        $fieldMap = [];
        foreach ($fields as $field) {
            if (isset($field['key'])) {
                $fieldMap[$field['key']] = [
                    'label' => $field['label'] ?? $field['key'],
                    'type' => $field['type'] ?? 'text'
                ];
            }
        }

        $detailedAnswers = $answers->map(function ($ans) use ($fieldMap) {
            $meta = $fieldMap[$ans->field_key] ?? ['label' => $ans->field_key, 'type' => 'text'];
            
            // Decodes checkbox arrays
            $val = $ans->answer_value;
            if ($meta['type'] === 'checkbox') {
                $decoded = json_decode($val, true);
                if (is_array($decoded)) {
                    $val = implode(', ', $decoded);
                }
            }

            return [
                'key' => $ans->field_key,
                'label' => $meta['label'],
                'type' => $meta['type'],
                'value' => $val,
                'is_file' => $meta['type'] === 'file',
                'file_url' => $meta['type'] === 'file' && $val ? asset('storage/' . $val) : null
            ];
        });

        return response()->json([
            'id' => $submission->id,
            'ip_address' => $submission->ip_address,
            'user_agent' => $submission->user_agent,
            'duration_seconds' => $submission->duration_seconds,
            'submitted_at' => $submission->created_at->format('Y-m-d H:i:s'),
            'answers' => $detailedAnswers
        ]);
    }

    /**
     * Export form submissions as a filtered CSV download stream.
     */
    public function export(Request $request, Form $form)
    {
        // Enforce form ownership
        if ($form->user_id !== Auth::id()) {
            abort(403, 'Unauthorized action.');
        }

        $search = $request->query('search');
        
        // Extract non-section fields from form schema for mapping columns
        $fields = collect($form->schema['fields'] ?? [])->where('type', '!=', 'section')->values();

        // Build headers list
        $headers = [
            'Submission ID',
            'Submitted At',
            'IP Address',
            'Duration (Seconds)'
        ];
        foreach ($fields as $field) {
            $headers[] = $field['label'] ?? $field['key'];
        }

        $fileName = 'submissions-' . $form->id . '-' . date('Ymd-His') . '.csv';

        $responseHeaders = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $fileName . '"',
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ];

        $callback = function () use ($form, $search, $fields, $headers) {
            $file = fopen('php://output', 'w');
            
            // Write column headers
            fputcsv($file, $headers);

            // Fetch and write rows via lazy cursor chunking to prevent out-of-memory errors
            $query = $this->submissionService->getExportDataQuery($form, $search);
            
            foreach ($query->cursor() as $sub) {
                $row = [
                    $sub->id,
                    $sub->created_at->format('Y-m-d H:i:s'),
                    $sub->ip_address,
                    $sub->duration_seconds ?? 'N/A'
                ];

                // Write dynamic answers matching schema fields
                foreach ($fields as $field) {
                    $ans = $sub->answers->firstWhere('field_key', $field['key']);
                    $val = $ans ? $ans->answer_value : '';

                    if ($field['type'] === 'checkbox' && $val) {
                        $decoded = json_decode($val, true);
                        if (is_array($decoded)) {
                            $val = implode(', ', $decoded);
                        }
                    }

                    $row[] = $val;
                }

                fputcsv($file, $row);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $responseHeaders);
    }
}
