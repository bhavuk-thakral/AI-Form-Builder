<?php

namespace App\Http\Controllers;

use App\Models\Form;
use App\Services\FormBuilderService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

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

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'status' => ['required', 'string', 'in:draft,active,archived'],
        ]);

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
