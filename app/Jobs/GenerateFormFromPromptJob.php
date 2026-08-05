<?php

namespace App\Jobs;

use App\Models\Form;
use App\Models\FormVersion;
use App\Models\ActivityLog;
use App\Services\AIService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class GenerateFormFromPromptJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $form;
    protected $prompt;
    protected $userId;

    /**
     * Create a new job instance.
     */
    public function __construct(Form $form, string $prompt, int $userId)
    {
        $this->form = $form;
        $this->prompt = $prompt;
        $this->userId = $userId;
    }

    /**
     * Execute the job.
     */
    public function handle(AIService $aiService): void
    {
        Log::info("Starting AI Form Generation Job for Form ID: {$this->form->id}");

        // Call the AI service
        $result = $aiService->generateFormSchema($this->prompt);

        DB::transaction(function () use ($result) {
            if ($result['success']) {
                $schema = $result['schema'];

                // Update form properties
                $this->form->update([
                    'title' => $schema['title'] ?? $this->form->title,
                    'description' => $schema['description'] ?? $this->form->description,
                    'schema' => $schema,
                    'status' => 'draft', // ready for editing
                ]);

                // Create initial version record
                FormVersion::create([
                    'form_id' => $this->form->id,
                    'version_number' => 1,
                    'schema' => $schema,
                    'created_by' => $this->userId,
                ]);

                // Record successful generation log with tokens & latency
                ActivityLog::create([
                    'user_id' => $this->userId,
                    'form_id' => $this->form->id,
                    'action' => 'generated',
                    'description' => "AI generated a new form schema based on user prompt.",
                    'metadata' => [
                        'status' => 'success',
                        'model' => $result['model'],
                        'prompt_tokens' => $result['prompt_tokens'],
                        'completion_tokens' => $result['completion_tokens'],
                        'total_tokens' => $result['total_tokens'],
                        'latency_ms' => $result['latency'],
                        'prompt' => $this->prompt
                    ],
                    'ip_address' => '127.0.0.1',
                ]);

                Log::info("AI Form Generation completed successfully for Form ID: {$this->form->id}");
            } else {
                // If AI fails
                $this->form->update([
                    'title' => 'Generation Failed',
                    'description' => 'We could not generate the form based on your prompt. Check the activity log.',
                    'status' => 'failed',
                ]);

                ActivityLog::create([
                    'user_id' => $this->userId,
                    'form_id' => $this->form->id,
                    'action' => 'failed',
                    'description' => "AI Form generation failed.",
                    'metadata' => [
                        'status' => 'error',
                        'error_message' => $result['error'] ?? 'Unknown error occurred.',
                        'prompt' => $this->prompt
                    ],
                    'ip_address' => '127.0.0.1',
                ]);

                Log::error("AI Form Generation Job failed for Form ID: {$this->form->id}");
            }
        });
    }
}
