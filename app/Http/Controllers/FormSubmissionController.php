<?php

namespace App\Http\Controllers;

use App\Models\Form;
use App\Models\Submission;
use App\Services\SubmissionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class FormSubmissionController extends Controller
{
    protected $submissionService;

    /**
     * Inject SubmissionService.
     */
    public function __construct(SubmissionService $submissionService)
    {
        $this->submissionService = $submissionService;
    }

    /**
     * Display a list of submissions for a specific form.
     */
    public function index(Request $request, Form $form)
    {
        // Enforce form ownership
        if ($form->user_id !== Auth::id()) {
            abort(403, 'Unauthorized action.');
        }

        $search = $request->query('search');
        $submissions = $this->submissionService->getSubmissionsForForm($form, $search, 10);

        return view('forms.submissions', compact('form', 'submissions', 'search'));
    }

    /**
     * Get detailed answers for a single submission (JSON endpoint for modals).
     */
    public function show(Submission $submission)
    {
        // Enforce form ownership check via relation
        if ($submission->form->user_id !== Auth::id()) {
            abort(403, 'Unauthorized action.');
        }

        // Load all answers
        $answers = $submission->answers;
        
        // Map answers with their labels from the form schema for convenience
        $fields = $submission->form->schema['fields'] ?? [];
        $fieldMap = [];
        foreach ($fields as $field) {
            if (isset($field['key'])) {
                $fieldMap[$field['key']] = [
                    'label' => $field['label'] ?? $field['key'],
                    'type' => $field['type'] ?? 'text'
                ];
            }
        }

        $detailedAnswers = $answers->map(function ($ans) use ($fieldMap) {
            $meta = $fieldMap[$ans->field_key] ?? ['label' => $ans->field_key, 'type' => 'text'];
            
            // Decodes checkbox arrays
            $val = $ans->answer_value;
            if ($meta['type'] === 'checkbox') {
                $decoded = json_decode($val, true);
                if (is_array($decoded)) {
                    $val = implode(', ', $decoded);
                }
            }

            return [
                'key' => $ans->field_key,
                'label' => $meta['label'],
                'type' => $meta['type'],
                'value' => $val,
                'is_file' => $meta['type'] === 'file',
                'file_url' => $meta['type'] === 'file' && $val ? asset('storage/' . $val) : null
            ];
        });

        return response()->json([
            'id' => $submission->id,
            'ip_address' => $submission->ip_address,
            'user_agent' => $submission->user_agent,
            'duration_seconds' => $submission->duration_seconds,
            'submitted_at' => $submission->created_at->format('Y-m-d H:i:s'),
            'answers' => $detailedAnswers
        ]);
    }
}
