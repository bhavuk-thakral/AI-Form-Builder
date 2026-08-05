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

    /**
     * Get paginated and filtered submissions list for a form.
     */
    public function getSubmissionsForForm(Form $form, ?string $search = null, int $perPage = 10)
    {
        $query = $form->submissions()->with(['answers', 'version']);

        if (!empty($search)) {
            $query->where(function ($subQuery) use ($search) {
                // Search by answers value
                $subQuery->whereHas('answers', function ($ansQuery) use ($search) {
                    $ansQuery->where('answer_value', 'like', "%{$search}%");
                })
                // Or search by meta values (IP address)
                ->orWhere('ip_address', 'like', "%{$search}%");
            });
        }

        return $query->latest()->paginate($perPage);
    }

    /**
     * Get query builder instance for CSV export.
     */
    public function getExportDataQuery(Form $form, ?string $search = null)
    {
        $query = $form->submissions()->with(['answers', 'version']);

        if (!empty($search)) {
            $query->where(function ($subQuery) use ($search) {
                $subQuery->whereHas('answers', function ($ansQuery) use ($search) {
                    $ansQuery->where('answer_value', 'like', "%{$search}%");
                })->orWhere('ip_address', 'like', "%{$search}%");
            });
        }

        return $query->latest();
    }
}
