<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Form;
use App\Models\FormVersion;
use App\Models\Submission;
use App\Models\SubmissionAnswer;
use App\Models\ActivityLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DatabaseTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test database relationship mappings and casting behavior.
     */
    public function test_relationships_and_database_schema()
    {
        // Create User
        $user = User::factory()->create();

        // Create Form
        $schemaData = [
            'title' => 'Test Form Schema',
            'fields' => [
                ['key' => 'field_1', 'type' => 'text', 'label' => 'Label 1']
            ]
        ];

        $form = Form::create([
            'user_id' => $user->id,
            'title' => 'Test Title',
            'description' => 'Test Desc',
            'status' => 'draft',
            'schema' => $schemaData,
            'views_count' => 5,
            'share_token' => 'test-token-1234',
        ]);

        $this->assertEquals($user->id, $form->user->id);
        $this->assertIsArray($form->schema);
        $this->assertEquals('Test Form Schema', $form->schema['title']);

        // Create Form Version
        $version = FormVersion::create([
            'form_id' => $form->id,
            'version_number' => 1,
            'schema' => $schemaData,
            'description' => 'Initial Revision',
            'created_by' => $user->id,
        ]);

        $this->assertEquals($form->id, $version->form->id);
        $this->assertEquals($user->id, $version->creator->id);
        $this->assertCount(1, $form->versions);

        // Create Submission
        $submission = Submission::create([
            'form_id' => $form->id,
            'form_version_id' => $version->id,
            'ip_address' => '127.0.0.1',
            'user_agent' => 'PHPUnit Client',
            'duration_seconds' => 42,
        ]);

        $this->assertEquals($form->id, $submission->form->id);
        $this->assertEquals($version->id, $submission->version->id);
        $this->assertCount(1, $form->submissions);
        $this->assertCount(1, $version->submissions);

        // Create Answer
        $answer = SubmissionAnswer::create([
            'submission_id' => $submission->id,
            'field_key' => 'field_1',
            'answer_value' => 'User Answer Input',
        ]);

        $this->assertEquals($submission->id, $answer->submission->id);
        $this->assertCount(1, $submission->answers);

        // Create Activity Log
        $log = ActivityLog::create([
            'user_id' => $user->id,
            'form_id' => $form->id,
            'action' => 'created',
            'description' => 'User created form',
            'metadata' => ['payload_data' => 'info'],
            'ip_address' => '127.0.0.1',
        ]);

        $this->assertEquals($user->id, $log->user->id);
        $this->assertEquals($form->id, $log->form->id);
        $this->assertIsArray($log->metadata);
        $this->assertEquals('info', $log->metadata['payload_data']);
    }

    /**
     * Test that Database Seeder operates correctly.
     */
    public function test_database_seeder_runs()
    {
        $this->seed();

        $this->assertDatabaseHas('users', ['email' => 'bhavuk@test.com']);
        $this->assertDatabaseHas('forms', ['title' => 'Internship Application Form']);
        $this->assertDatabaseHas('form_versions', ['version_number' => 1]);
        $this->assertDatabaseHas('submissions', ['duration_seconds' => 120]);
        $this->assertDatabaseHas('submission_answers', ['field_key' => 'full_name', 'answer_value' => 'Alice Johnson']);
        $this->assertDatabaseHas('activity_logs', ['action' => 'created']);
    }
}
