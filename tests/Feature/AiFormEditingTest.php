<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Form;
use App\Models\FormVersion;
use App\Models\ActivityLog;
use App\Jobs\EditFormWithAIJob;
use App\Services\AIService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class AiFormEditingTest extends TestCase
{
    use RefreshDatabase;

    private $user;
    private $form;
    private $version1;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->user = User::factory()->create();

        $schema = [
            'title' => 'Core Contact Form',
            'fields' => [
                ['key' => 'email', 'type' => 'email', 'label' => 'Email Address']
            ]
        ];

        $this->form = Form::create([
            'user_id' => $this->user->id,
            'title' => 'Core Contact Form',
            'status' => 'draft',
            'schema' => $schema,
            'share_token' => 'ai-edit-slug-12',
        ]);

        $this->version1 = FormVersion::create([
            'form_id' => $this->form->id,
            'version_number' => 1,
            'schema' => $schema,
            'created_by' => $this->user->id,
        ]);
    }

    /**
     * Test guests are blocked from editing.
     */
    public function test_guests_cannot_trigger_ai_editing()
    {
        $response = $this->post(route('forms.ai-edit', $this->form->id), [
            'instruction' => 'Add phone number after email'
        ]);

        $response->assertStatus(302)->assertRedirect(route('login'));
    }

    /**
     * Test owners can trigger editing and dispatch job.
     */
    public function test_owner_can_trigger_ai_edit_and_dispatch_job()
    {
        Queue::fake();

        $response = $this->actingAs($this->user)->post(route('forms.ai-edit', $this->form->id), [
            'instruction' => 'Add phone number after email'
        ]);

        $response->assertRedirect(route('forms.edit', $this->form->id));
        $response->assertSessionHas('toast_success');

        $this->form->refresh();
        $this->assertEquals('generating', $this->form->status);

        Queue::assertPushed(EditFormWithAIJob::class, function ($job) {
            return $job->connection === null; // default connection
        });
    }

    /**
     * Test background job modifies schema and increments version.
     */
    public function test_editing_queue_job_modifies_schema_and_version()
    {
        $this->form->update(['status' => 'generating']);

        $job = new EditFormWithAIJob($this->form, 'Add phone number', $this->user->id);
        $job->handle(app(AIService::class));

        $this->form->refresh();
        $this->assertEquals('draft', $this->form->status);
        $this->assertIsArray($this->form->schema);
        
        // Assert field has been appended by mock editor
        $this->assertCount(2, $this->form->schema['fields']);
        $this->assertEquals('phone_number', $this->form->schema['fields'][1]['key']);

        // Assert version number incremented
        $latestVersion = FormVersion::orderBy('version_number', 'desc')->first();
        $this->assertNotNull($latestVersion);
        $this->assertEquals(2, $latestVersion->version_number);
        $this->assertEquals($this->form->id, $latestVersion->form_id);

        // Assert activity log contains tokens and latency metadata
        $log = ActivityLog::where('action', 'updated_by_ai')->first();
        $this->assertNotNull($log);
        $this->assertEquals('success', $log->metadata['status']);
        $this->assertEquals(480, $log->metadata['total_tokens']);
        $this->assertEquals(2, $log->metadata['new_version']);
    }
}
