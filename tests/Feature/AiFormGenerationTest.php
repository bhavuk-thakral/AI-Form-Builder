<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Form;
use App\Models\FormVersion;
use App\Models\ActivityLog;
use App\Jobs\GenerateFormFromPromptJob;
use App\Services\AIService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class AiFormGenerationTest extends TestCase
{
    use RefreshDatabase;

    private $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    /**
     * Test guests are blocked.
     */
    public function test_guests_cannot_trigger_ai_generation()
    {
        $response = $this->post(route('forms.generate'), [
            'prompt' => 'Create a simple contact form'
        ]);

        $response->assertStatus(302)->assertRedirect(route('login'));
    }

    /**
     * Test user prompt dispatches queue job.
     */
    public function test_generating_form_creates_placeholder_and_dispatches_job()
    {
        Queue::fake();

        $response = $this->actingAs($this->user)->post(route('forms.generate'), [
            'prompt' => 'Create a student feedback survey'
        ]);

        $response->assertRedirect(route('dashboard'));
        $response->assertSessionHas('toast_success');

        // Check placeholder form
        $form = Form::first();
        $this->assertNotNull($form);
        $this->assertEquals('Generating form...', $form->title);
        $this->assertEquals('generating', $form->status);
        $this->assertEquals($this->user->id, $form->user_id);

        // Check queue job
        Queue::assertPushed(GenerateFormFromPromptJob::class, function ($job) use ($form) {
            return $job->connection === null; // default connection
        });
    }

    /**
     * Test job handles success.
     */
    public function test_queue_job_updates_form_layout_on_success()
    {
        $form = Form::create([
            'user_id' => $this->user->id,
            'title' => 'Generating form...',
            'description' => 'AI generation in progress...',
            'status' => 'generating',
            'schema' => ['fields' => []],
            'share_token' => 'ai-sandbox-tok-1',
        ]);

        $job = new GenerateFormFromPromptJob($form, 'Create a standard contact card', $this->user->id);
        
        // Handle job execution
        $job->handle(app(AIService::class));

        $form->refresh();
        $this->assertEquals('AI Contact Setup Form', $form->title);
        $this->assertEquals('draft', $form->status);
        $this->assertIsArray($form->schema);
        $this->assertCount(3, $form->schema['fields']);

        // Assert initial version created
        $version = FormVersion::first();
        $this->assertNotNull($version);
        $this->assertEquals($form->id, $version->form_id);
        $this->assertEquals(1, $version->version_number);

        // Assert activity log details
        $log = ActivityLog::first();
        $this->assertNotNull($log);
        $this->assertEquals($form->id, $log->form_id);
        $this->assertEquals('generated', $log->action);
        $this->assertEquals('success', $log->metadata['status']);
        $this->assertEquals('gpt-4o-mini-mocked', $log->metadata['model']);
        $this->assertEquals(355, $log->metadata['total_tokens']);
        $this->assertNotNull($log->metadata['latency_ms']);
    }
}
