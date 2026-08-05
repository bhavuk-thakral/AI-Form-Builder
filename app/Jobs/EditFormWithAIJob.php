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

class EditFormWithAIJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $form;
    protected $instruction;
    protected $userId;

    /**
     * Create a new job instance.
     */
    public function __construct(Form $form, string $instruction, int $userId)
    {
        $this->form = $form;
        $this->instruction = $instruction;
        $this->userId = $userId;
    }

    /**
     * Execute the job.
     */
    public function handle(AIService $aiService): void
    {
        Log::info("Starting AI Form Editing Job for Form ID: {$this->form->id}");

        $result = $aiService->editFormSchema($this->form->schema, $this->instruction);

        DB::transaction(function () use ($result) {
            if ($result['success']) {
                $schema = $result['schema'];

                // Increment version number
                $lastVersion = $this->form->versions()->max('version_number') ?: 1;
                $newVersionNumber = $lastVersion + 1;

                // Update form schema
                $this->form->update([
                    'title' => $schema['title'] ?? $this->form->title,
                    'description' => $schema['description'] ?? $this->form->description,
                    'schema' => $schema,
                    'status' => 'draft', // stays in draft editing mode
                ]);

                // Create new version history record
                FormVersion::create([
                    'form_id' => $this->form->id,
                    'version_number' => $newVersionNumber,
                    'schema' => $schema,
                    'created_by' => $this->userId,
                ]);

                // Record successful edit log with tokens & latency
                ActivityLog::create([
                    'user_id' => $this->userId,
                    'form_id' => $this->form->id,
                    'action' => 'updated_by_ai',
                    'description' => "Form schema edited by AI Co-pilot: \"{$this->instruction}\"",
                    'metadata' => [
                        'status' => 'success',
                        'model' => $result['model'],
                        'prompt_tokens' => $result['prompt_tokens'],
                        'completion_tokens' => $result['completion_tokens'],
                        'total_tokens' => $result['total_tokens'],
                        'latency_ms' => $result['latency'],
                        'instruction' => $this->instruction,
                        'new_version' => $newVersionNumber
                    ],
                    'ip_address' => '127.0.0.1',
                ]);

                Log::info("AI Form Editing completed successfully for Form ID: {$this->form->id}");
            } else {
                // If AI fails, restore form status
                $this->form->update([
                    'status' => 'draft',
                ]);

                ActivityLog::create([
                    'user_id' => $this->userId,
                    'form_id' => $this->form->id,
                    'action' => 'failed_ai_edit',
                    'description' => "AI Form edit instruction failed.",
                    'metadata' => [
                        'status' => 'error',
                        'error_message' => $result['error'] ?? 'Unknown error occurred.',
                        'instruction' => $this->instruction
                    ],
                    'ip_address' => '127.0.0.1',
                ]);

                Log::error("AI Form Editing Job failed for Form ID: {$this->form->id}");
            }
        });
    }
}
