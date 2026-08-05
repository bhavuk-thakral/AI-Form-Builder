<?php

namespace App\Services;

use App\Models\Form;
use App\Models\Submission;
use App\Models\SubmissionAnswer;
use App\Models\ActivityLog;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\UploadedFile;

class SubmissionService
{
    /**
     * Store a form submission and answers in database.
     */
    public function submitForm(Form $form, array $validatedData, string $ip, string $userAgent): Submission
    {
        return DB::transaction(function () use ($form, $validatedData, $ip, $userAgent) {
            // Get current version
            $latestVersion = $form->versions()->latest()->first();

            // Extract duration if sent
            $duration = $validatedData['duration_seconds'] ?? null;
            unset($validatedData['duration_seconds']);

            // Create submission record
            $submission = Submission::create([
                'form_id' => $form->id,
                'form_version_id' => $latestVersion ? $latestVersion->id : null,
                'ip_address' => $ip,
                'user_agent' => $userAgent,
                'duration_seconds' => $duration ? (int)$duration : null,
            ]);

            // Save each answer
            foreach ($form->schema['fields'] as $field) {
                $key = $field['key'] ?? null;
                if (!$key || $field['type'] === 'section') {
                    continue;
                }

                $value = $validatedData[$key] ?? null;

                // Handle file upload processing
                if ($field['type'] === 'file' && $value instanceof UploadedFile) {
                    $path = $value->store('submissions', 'public');
                    $value = $path;
                }

                // Handle array inputs (like checkboxes)
                if (is_array($value)) {
                    $value = json_encode($value);
                }

                SubmissionAnswer::create([
                    'submission_id' => $submission->id,
                    'field_key' => $key,
                    'answer_value' => $value,
                ]);
            }

            // Create activity log
            ActivityLog::create([
                'user_id' => null, // public submitter
                'form_id' => $form->id,
                'action' => 'submitted',
                'description' => "New public submission received for form '{$form->title}'.",
                'ip_address' => $ip,
            ]);

            return $submission;
        });
    }
}
