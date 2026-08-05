<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Form;
use App\Models\FormVersion;
use App\Models\ActivityLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\IOFactory as WordIO;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\IOFactory as SheetIO;
use Tests\TestCase;

class DocumentImportTest extends TestCase
{
    use RefreshDatabase;

    private $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    /**
     * Test guest users are blocked from importing.
     */
    public function test_guests_cannot_import_forms()
    {
        $response = $this->post(route('forms.import'), [
            'import_file' => UploadedFile::fake()->create('form.docx', 100)
        ]);

        $response->assertStatus(302)->assertRedirect(route('login'));
    }

    /**
     * Test importing DOCX file parses headings and questions.
     */
    public function test_import_docx_extracts_headings_and_questions()
    {
        // 1. Create a real temporary docx file using PhpWord with correct file extension
        $phpWord = new PhpWord();
        $section = $phpWord->addSection();
        
        // Add heading starting with SECTION prefix for fallback matching
        $section->addTitle('SECTION: Personal Information');
        
        // Add question paragraphs
        $section->addText('What is your full name?');
        $section->addText('What is your email address?');
        $section->addText('Upload your photo');

        $tempDocx = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'test_' . uniqid() . '.docx';
        $objWriter = WordIO::createWriter($phpWord, 'Word2007');
        $objWriter->save($tempDocx);

        // 2. Upload file
        $uploadedFile = new UploadedFile(
            $tempDocx,
            'application.docx',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            null,
            true // test mode
        );

        $response = $this->actingAs($this->user)->post(route('forms.import'), [
            'import_file' => $uploadedFile
        ]);

        // Clean up temp file
        if (file_exists($tempDocx)) {
            unlink($tempDocx);
        }

        // 3. Assertions
        $form = Form::first();
        $this->assertNotNull($form);
        $response->assertRedirect(route('forms.edit', $form->id));
        $this->assertEquals('Imported Form - application', $form->title);
        $this->assertCount(4, $form->schema['fields']);

        // Heading section check
        $this->assertEquals('section', $form->schema['fields'][0]['type']);
        $this->assertEquals('SECTION: Personal Information', $form->schema['fields'][0]['label']);

        // Fields check
        $this->assertEquals('text', $form->schema['fields'][1]['type']);
        $this->assertEquals('What is your full name?', $form->schema['fields'][1]['label']);

        $this->assertEquals('email', $form->schema['fields'][2]['type']); // classified as email due to keyword
        $this->assertEquals('What is your email address?', $form->schema['fields'][2]['label']);

        $this->assertEquals('file', $form->schema['fields'][3]['type']); // classified as file due to keyword
        
        // Check version 1
        $version = FormVersion::first();
        $this->assertNotNull($version);
        $this->assertEquals(1, $version->version_number);

        // Check log
        $this->assertDatabaseHas('activity_logs', [
            'form_id' => $form->id,
            'action' => 'imported',
        ]);
    }

    /**
     * Test importing XLSX columns to form fields.
     */
    public function test_import_xlsx_extracts_column_headers()
    {
        // 1. Create a real spreadsheet using PhpSpreadsheet with correct file extension
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        
        // Set column header labels in row 1
        $sheet->setCellValue('A1', 'Candidate Name');
        $sheet->setCellValue('B1', 'Email ID');
        $sheet->setCellValue('C1', 'Interview Date');

        $tempXlsx = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'test_' . uniqid() . '.xlsx';
        $writer = SheetIO::createWriter($spreadsheet, 'Xlsx');
        $writer->save($tempXlsx);

        // 2. Upload file
        $uploadedFile = new UploadedFile(
            $tempXlsx,
            'survey.xlsx',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            null,
            true // test mode
        );

        $response = $this->actingAs($this->user)->post(route('forms.import'), [
            'import_file' => $uploadedFile
        ]);

        // Clean up temp file
        if (file_exists($tempXlsx)) {
            unlink($tempXlsx);
        }

        // 3. Assertions
        $form = Form::first();
        $this->assertNotNull($form);
        $response->assertRedirect(route('forms.edit', $form->id));
        $this->assertEquals('Imported Form - survey', $form->title);
        $this->assertCount(3, $form->schema['fields']);

        // Check columns mapping
        $this->assertEquals('text', $form->schema['fields'][0]['type']);
        $this->assertEquals('Candidate Name', $form->schema['fields'][0]['label']);

        $this->assertEquals('email', $form->schema['fields'][1]['type']);
        $this->assertEquals('Email ID', $form->schema['fields'][1]['label']);

        $this->assertEquals('date', $form->schema['fields'][2]['type']);
        $this->assertEquals('Interview Date', $form->schema['fields'][2]['label']);
    }
}
