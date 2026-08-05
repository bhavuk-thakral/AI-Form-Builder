<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Form;
use App\Models\FormVersion;
use App\Models\Submission;
use App\Models\SubmissionAnswer;
use App\Models\ActivityLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PublicFormTest extends TestCase
{
    use RefreshDatabase;

    private $user;
    private $form;
    private $version;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();

        $schema = [
            'title' => 'Job Application Survey',
            'fields' => [
                [
                    'key' => 'applicant_name',
                    'type' => 'text',
                    'label' => 'Full Name',
                    'required' => true,
                ],
                [
                    'key' => 'applicant_email',
                    'type' => 'email',
                    'label' => 'Email Address',
                    'required' => true,
                ],
                [
                    'key' => 'years_experience',
                    'type' => 'number',
                    'label' => 'Experience Years',
                    'required' => false,
                    'validations' => ['min:0'],
                ],
                [
                    'key' => 'resume',
                    'type' => 'file',
                    'label' => 'Upload CV',
                    'required' => true,
                    'validations' => ['max:1024', 'mimes:pdf'],
                ]
            ]
        ];

        $this->form = Form::create([
            'user_id' => $this->user->id,
            'title' => 'Job Application Survey',
            'status' => 'active',
            'schema' => $schema,
            'share_token' => 'active-slug-123',
        ]);

        $this->version = FormVersion::create([
            'form_id' => $this->form->id,
            'version_number' => 1,
            'schema' => $schema,
            'created_by' => $this->user->id,
        ]);
    }

    /**
     * Test public form renders properly and view counter increments.
     */
    public function test_public_form_renders_and_increments_views()
    {
        $this->assertEquals(0, $this->form->views_count);

        $response = $this->get(route('forms.public.show', 'active-slug-123'));

        $response->assertStatus(200);
        $response->assertSee('Job Application Survey');
        $response->assertSee('Full Name');
        $response->assertSee('Email Address');
        $response->assertSee('Upload CV');

        $this->form->refresh();
        $this->assertEquals(1, $this->form->views_count);
    }

    /**
     * Test draft forms are blocked.
     */
    public function test_draft_forms_return_404()
    {
        $draftForm = Form::create([
            'user_id' => $this->user->id,
            'title' => 'Draft Sandbox',
            'status' => 'draft',
            'schema' => ['fields' => []],
            'share_token' => 'draft-slug-456',
        ]);

        $response = $this->get(route('forms.public.show', 'draft-slug-456'));
        $response->assertStatus(404);
    }

    /**
     * Test submissions fail dynamic validation.
     */
    public function test_invalid_submissions_fail_validation()
    {
        $response = $this->post(route('forms.public.submit', 'active-slug-123'), [
            'applicant_name' => '', // blank required field
            'applicant_email' => 'invalid-email-string', // bad format
            'years_experience' => -2, // fails min:0 validation
            // missing required file upload
        ]);

        $response->assertSessionHasErrors(['applicant_name', 'applicant_email', 'years_experience', 'resume']);
        $this->assertEquals(0, Submission::count());
    }

    /**
     * Test submissions save and store files.
     */
    public function test_valid_submissions_store_data_and_files()
    {
        Storage::fake('public');
        $fakeFile = UploadedFile::fake()->create('cv.pdf', 500, 'application/pdf');

        $response = $this->post(route('forms.public.submit', 'active-slug-123'), [
            'applicant_name' => 'Alice Dev',
            'applicant_email' => 'alice@dev.com',
            'years_experience' => 4,
            'resume' => $fakeFile,
            'duration_seconds' => 75, // mock timer response
        ]);

        $response->assertStatus(200);
        $response->assertSee('Thank You!');

        // Check Submission metadata
        $submission = Submission::first();
        $this->assertNotNull($submission);
        $this->assertEquals($this->form->id, $submission->form_id);
        $this->assertEquals($this->version->id, $submission->form_version_id);
        $this->assertEquals(75, $submission->duration_seconds);

        // Check Answers table records
        $this->assertDatabaseHas('submission_answers', [
            'submission_id' => $submission->id,
            'field_key' => 'applicant_name',
            'answer_value' => 'Alice Dev',
        ]);
        $this->assertDatabaseHas('submission_answers', [
            'submission_id' => $submission->id,
            'field_key' => 'applicant_email',
            'answer_value' => 'alice@dev.com',
        ]);
        $this->assertDatabaseHas('submission_answers', [
            'submission_id' => $submission->id,
            'field_key' => 'years_experience',
            'answer_value' => '4',
        ]);

        // Check File storage upload path
        $cvAnswer = SubmissionAnswer::where('submission_id', $submission->id)
            ->where('field_key', 'resume')->first();
        $this->assertNotNull($cvAnswer);
        $this->assertStringContainsString('submissions/', $cvAnswer->answer_value);
        Storage::disk('public')->assertExists($cvAnswer->answer_value);

        // Check activity log
        $this->assertDatabaseHas('activity_logs', [
            'form_id' => $this->form->id,
            'action' => 'submitted',
        ]);
    }
}
