<?php

namespace App\Http\Controllers;

use App\Models\Form;
use App\Services\FormBuilderService;
use App\Jobs\GenerateFormFromPromptJob;
use App\Jobs\EditFormWithAIJob;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class FormController extends Controller
{
    protected $formBuilderService;

    /**
     * Inject FormBuilderService.
     */
    public function __construct(FormBuilderService $formBuilderService)
    {
        $this->formBuilderService = $formBuilderService;
    }

    /**
     * Redirect to the dashboard forms listing.
     */
    public function index()
    {
        return redirect()->route('dashboard');
    }

    /**
     * Create a new draft form and redirect to builder.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
        ]);

        $form = $this->formBuilderService->createForm($validated, Auth::id());

        return redirect()->route('forms.edit', $form->id)
            ->with('toast_success', "Form '{$form->title}' created successfully! You are now in the canvas builder.");
    }

    /**
     * Asynchronously generate form layout from an AI prompt.
     */
    public function generate(Request $request)
    {
        $request->validate([
            'prompt' => ['required', 'string', 'min:5', 'max:1000'],
        ]);

        $prompt = $request->input('prompt');

        // Create placeholder form with status "generating"
        $form = Form::create([
            'user_id' => Auth::id(),
            'title' => 'Generating form...',
            'description' => "AI is compiling layout details for: \"{$prompt}\" in the background...",
            'status' => 'generating',
            'schema' => ['fields' => []],
            'share_token' => Str::random(32),
        ]);

        // Dispatch background queue job
        GenerateFormFromPromptJob::dispatch($form, $prompt, Auth::id());

        return redirect()->route('dashboard')
            ->with('toast_success', "AI Form generation started! The form is compiling in the background and will show up shortly.");
    }

    /**
     * Dispatch background queue job to edit the form with AI instructions.
     */
    public function aiEdit(Request $request, Form $form)
    {
        // Enforce form ownership
        if ($form->user_id !== Auth::id()) {
            abort(403, 'Unauthorized action.');
        }

        $request->validate([
            'instruction' => ['required', 'string', 'min:5', 'max:1000'],
        ]);

        // Set status to generating so the workspace locks with loading indicators
        $form->update([
            'status' => 'generating',
        ]);

        // Dispatch background queue job
        EditFormWithAIJob::dispatch($form, $request->input('instruction'), Auth::id());

        return redirect()->route('forms.edit', $form->id)
            ->with('toast_success', 'AI Co-pilot is updating your form in the background. The canvas will reload shortly.');
    }

    /**
     * Show the drag & drop form builder canvas.
     */
    public function edit(Form $form)
    {
        // Enforce user authorization check
        if ($form->user_id !== Auth::id()) {
            abort(403, 'Unauthorized action.');
        }

        return view('forms.builder', compact('form'));
    }

    /**
     * Update general settings of a form.
     */
    public function update(Request $request, Form $form)
    {
        if ($form->user_id !== Auth::id()) {
            abort(403, 'Unauthorized action.');
        }

        $data = $request->all();
        if ($request->has('schema') && is_string($request->input('schema'))) {
            $data['schema'] = json_decode($request->input('schema'), true);
        }

        $validated = validator($data, [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'status' => ['required', 'string', 'in:draft,active,archived'],
            'schema' => ['nullable', 'array'],
        ])->validate();

        $this->formBuilderService->updateForm($form, $validated, Auth::id());

        return redirect()->route('dashboard')
            ->with('toast_success', "Form settings updated successfully.");
    }

    /**
     * Delete a form.
     */
    public function destroy(Form $form)
    {
        if ($form->user_id !== Auth::id()) {
            abort(403, 'Unauthorized action.');
        }

        $title = $form->title;
        $this->formBuilderService->deleteForm($form, Auth::id());

        return redirect()->route('dashboard')
            ->with('toast_success', "Form '{$title}' deleted successfully.");
    }
}
