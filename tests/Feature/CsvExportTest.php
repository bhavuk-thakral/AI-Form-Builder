<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Form;
use App\Models\FormVersion;
use App\Models\Submission;
use App\Models\SubmissionAnswer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CsvExportTest extends TestCase
{
    use RefreshDatabase;

    private $user;
    private $otherUser;
    private $form;
    private $submission1;
    private $submission2;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->otherUser = User::factory()->create();

        $schema = [
            'title' => 'Export Sandbox',
            'fields' => [
                ['key' => 'name_key', 'type' => 'text', 'label' => 'Display Name'],
                ['key' => 'comment_key', 'type' => 'textarea', 'label' => 'Comments']
            ]
        ];

        $this->form = Form::create([
            'user_id' => $this->user->id,
            'title' => 'Export Sandbox',
            'status' => 'active',
            'schema' => $schema,
            'share_token' => 'export-slug-101',
        ]);

        $version = FormVersion::create([
            'form_id' => $this->form->id,
            'version_number' => 1,
            'schema' => $schema,
            'created_by' => $this->user->id,
        ]);

        // Submission 1: Alice
        $this->submission1 = Submission::create([
            'form_id' => $this->form->id,
            'form_version_id' => $version->id,
            'ip_address' => '127.0.0.1',
            'duration_seconds' => 30,
        ]);
        SubmissionAnswer::create([
            'submission_id' => $this->submission1->id,
            'field_key' => 'name_key',
            'answer_value' => 'Alice Dev',
        ]);
        SubmissionAnswer::create([
            'submission_id' => $this->submission1->id,
            'field_key' => 'comment_key',
            'answer_value' => 'Great builder layout!',
        ]);

        // Submission 2: Bob
        $this->submission2 = Submission::create([
            'form_id' => $this->form->id,
            'form_version_id' => $version->id,
            'ip_address' => '10.0.0.5',
            'duration_seconds' => 90,
        ]);
        SubmissionAnswer::create([
            'submission_id' => $this->submission2->id,
            'field_key' => 'name_key',
            'answer_value' => 'Bob Architect',
        ]);
        SubmissionAnswer::create([
            'submission_id' => $this->submission2->id,
            'field_key' => 'comment_key',
            'answer_value' => 'Scalable structure design.',
        ]);
    }

    /**
     * Test guests are blocked from exporting.
     */
    public function test_guests_cannot_export_csv()
    {
        $response = $this->get(route('forms.submissions.export', $this->form->id));
        $response->assertStatus(302)->assertRedirect(route('login'));
    }

    /**
     * Test other users are blocked.
     */
    public function test_other_users_cannot_export_csv()
    {
        $response = $this->actingAs($this->otherUser)->get(route('forms.submissions.export', $this->form->id));
        $response->assertStatus(403);
    }

    /**
     * Test form owner can download complete CSV streamed response.
     */
    public function test_owner_can_export_csv_successfully()
    {
        $response = $this->actingAs($this->user)->get(route('forms.submissions.export', $this->form->id));

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'text/csv; charset=utf-8');
        $response->assertHeader('Content-Disposition', 'attachment; filename="submissions-' . $this->form->id . '-' . date('Ymd-His') . '.csv"');

        // Capture streamed content
        ob_start();
        $response->sendContent();
        $content = ob_get_clean();

        // Check CSV content headers
        $this->assertStringContainsString('Submission ID', $content);
        $this->assertStringContainsString('Submitted At', $content);
        $this->assertStringContainsString('IP Address', $content);
        $this->assertStringContainsString('Duration (Seconds)', $content);
        $this->assertStringContainsString('Display Name', $content);
        $this->assertStringContainsString('Comments', $content);
        
        // Check rows
        $this->assertStringContainsString('Alice Dev', $content);
        $this->assertStringContainsString('Great builder layout!', $content);
        $this->assertStringContainsString('Bob Architect', $content);
        $this->assertStringContainsString('Scalable structure design.', $content);
    }

    /**
     * Test search-filtered CSV exports.
     */
    public function test_owner_can_export_filtered_csv()
    {
        $response = $this->actingAs($this->user)->get(route('forms.submissions.export', $this->form->id) . '?search=Bob');

        $response->assertStatus(200);
        
        ob_start();
        $response->sendContent();
        $content = ob_get_clean();

        // Check CSV filters output
        $this->assertStringContainsString('Bob Architect', $content);
        $this->assertStringContainsString('Scalable structure design.', $content);
        
        // Should NOT contain Alice
        $this->assertStringNotContainsString('Alice Dev', $content);
    }
}
