<?php

namespace App\Http\Controllers;

use App\Services\FormBuilderService;
use App\Services\SubmissionService;
use Illuminate\Http\Request;

class PublicFormController extends Controller
{
    protected $formBuilderService;
    protected $submissionService;

    /**
     * Inject services.
     */
    public function __construct(FormBuilderService $formBuilderService, SubmissionService $submissionService)
    {
        $this->formBuilderService = $formBuilderService;
        $this->submissionService = $submissionService;
    }

    /**
     * Render the public form page.
     */
    public function show(string $share_token)
    {
        $form = $this->formBuilderService->getFormByShareToken($share_token);

        if (!$form || $form->status !== 'active') {
            abort(404, 'Form is not active or cannot be found.');
        }

        // Increment views count
        $this->formBuilderService->incrementViews($form);

        return view('forms.public', compact('form'));
    }

    /**
     * Handle public form response submission.
     */
    public function submit(Request $request, string $share_token)
    {
        $form = $this->formBuilderService->getFormByShareToken($share_token);

        if (!$form || $form->status !== 'active') {
            abort(404, 'Form is not active or cannot be found.');
        }

        // Compile validation rules dynamically from schema
        $rules = $this->formBuilderService->generateValidationRules($form);
        
        // Append stopwatch field rules
        $rules['duration_seconds'] = ['nullable', 'integer'];

        $validated = $request->validate($rules);

        $this->submissionService->submitForm(
            $form, 
            $validated, 
            $request->ip(), 
            $request->userAgent()
        );

        return view('forms.submitted', compact('form'));
    }
}
