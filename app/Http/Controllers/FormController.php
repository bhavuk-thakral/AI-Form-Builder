<?php

namespace App\Http\Controllers;

use App\Models\Form;
use App\Models\FormVersion;
use App\Services\FormBuilderService;
use App\Jobs\GenerateFormFromPromptJob;
use App\Jobs\EditFormWithAIJob;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

use App\Services\DocumentImportService;

class FormController extends Controller
{
    protected $formBuilderService;
    protected $documentImportService;

    /**
     * Inject services.
     */
    public function __construct(FormBuilderService $formBuilderService, DocumentImportService $documentImportService)
    {
        $this->formBuilderService = $formBuilderService;
        $this->documentImportService = $documentImportService;
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

    /**
     * Import form layouts from file attachments (DOCX/XLSX).
     */
    public function import(Request $request)
    {
        $request->validate([
            'import_file' => ['required', 'file', 'max:5120'], // max 5MB
        ]);

        $file = $request->file('import_file');
        $extension = strtolower($file->getClientOriginalExtension());
        if (!in_array($extension, ['docx', 'xlsx'])) {
            return redirect()->back()
                ->with('toast_error', "Failed to import form document: Invalid file format. Only DOCX and XLSX documents are supported.");
        }

        try {
            $form = $this->documentImportService->importFormFromFile($file, Auth::id());

            return redirect()->route('forms.edit', $form->id)
                ->with('toast_success', "Form '{$form->title}' imported successfully! You can now customize details.");
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('toast_error', "Failed to import form document: " . $e->getMessage());
        }
    }

    /**
     * Restore a specific form version history record.
     */
    public function restoreVersion(Form $form, FormVersion $version)
    {
        if ($form->user_id !== Auth::id() || $version->form_id !== $form->id) {
            abort(403, 'Unauthorized action.');
        }

        // We create a NEW version record for the restoration action so we don't lose current state!
        $nextVersionNumber = $form->versions()->max('version_number') + 1;

        // Perform restore
        $form->update([
            'schema' => $version->schema,
            'title' => $version->schema['title'] ?? $form->title,
            'description' => $version->schema['description'] ?? $form->description,
        ]);

        // Save new version checkpoint representing the restore event
        FormVersion::create([
            'form_id' => $form->id,
            'version_number' => $nextVersionNumber,
            'schema' => $version->schema,
            'created_by' => Auth::id(),
        ]);

        // Log restore event
        \App\Models\ActivityLog::create([
            'user_id' => Auth::id(),
            'form_id' => $form->id,
            'action' => 'restored',
            'description' => "Restored form layout back to Version #{$version->version_number}.",
            'ip_address' => request()->ip() ?? '127.0.0.1',
        ]);

        return redirect()->route('forms.edit', $form->id)
            ->with('toast_success', "Form successfully restored back to Version #{$version->version_number}!");
    }

    /**
     * Create a new form pre-populated from a template.
     */
    public function createFromTemplate(Request $request)
    {
        $request->validate([
            'template_key' => ['required', 'string', 'in:contact,feedback,event'],
        ]);

        $templates = [
            'contact' => [
                'title' => 'Contact Us Support Form',
                'description' => 'Collect support tickets, queries, and feedback from your site visitors.',
                'fields' => [
                    [
                        'id' => 'f_contact_1',
                        'key' => 'contact_name',
                        'type' => 'text',
                        'label' => 'Full Name',
                        'placeholder' => 'Enter your name...',
                        'required' => true,
                    ],
                    [
                        'id' => 'f_contact_2',
                        'key' => 'contact_email',
                        'type' => 'email',
                        'label' => 'Email Address',
                        'placeholder' => 'e.g., you@domain.com',
                        'required' => true,
                        'validations' => ['email'],
                    ],
                    [
                        'id' => 'f_contact_3',
                        'key' => 'contact_message',
                        'type' => 'textarea',
                        'label' => 'Message / Question',
                        'placeholder' => 'How can we help you today?',
                        'required' => true,
                    ]
                ]
            ],
            'feedback' => [
                'title' => 'Customer Feedback Survey',
                'description' => 'Gather insights on client satisfaction and service ratings.',
                'fields' => [
                    [
                        'id' => 'f_feed_1',
                        'key' => 'feedback_name',
                        'type' => 'text',
                        'label' => 'Full Name',
                        'placeholder' => 'Anonymous (Optional)',
                        'required' => false,
                    ],
                    [
                        'id' => 'f_feed_2',
                        'key' => 'satisfaction_rating',
                        'type' => 'rating',
                        'label' => 'Overall Satisfaction',
                        'required' => true,
                        'default_value' => '5',
                    ],
                    [
                        'id' => 'f_feed_3',
                        'key' => 'satisfaction_comments',
                        'type' => 'textarea',
                        'label' => 'Additional Comments',
                        'placeholder' => 'What can we improve?',
                        'required' => false,
                    ]
                ]
            ],
            'event' => [
                'title' => 'Annual Summit Registration Form',
                'description' => 'Reserve guest list spots, gather contact data, and note preferences.',
                'fields' => [
                    [
                        'id' => 'f_evt_1',
                        'key' => 'registrant_name',
                        'type' => 'text',
                        'label' => 'Full Name',
                        'placeholder' => 'Enter your full name...',
                        'required' => true,
                    ],
                    [
                        'id' => 'f_evt_2',
                        'key' => 'registrant_email',
                        'type' => 'email',
                        'label' => 'Email Address',
                        'placeholder' => 'e.g., applicant@example.com',
                        'required' => true,
                        'validations' => ['email'],
                    ],
                    [
                        'id' => 'f_evt_3',
                        'key' => 'meal_preference',
                        'type' => 'dropdown',
                        'label' => 'Meal Preference',
                        'placeholder' => 'Select preference...',
                        'required' => true,
                        'options' => ['Vegetarian', 'Vegan', 'Non-Vegetarian'],
                    ],
                    [
                        'id' => 'f_evt_4',
                        'key' => 'sessions_attending',
                        'type' => 'checkbox',
                        'label' => 'Select Sessions to Attend',
                        'required' => false,
                        'options' => [
                            'Keynote Address', 
                            'Backend Architecture Workshop', 
                            'AI & Machine Learning Panel'
                        ],
                    ]
                ]
            ]
        ];

        $key = $request->input('template_key');
        $tpl = $templates[$key];

        $form = \DB::transaction(function () use ($tpl) {
            $schema = [
                'title' => $tpl['title'],
                'description' => $tpl['description'],
                'fields' => $tpl['fields']
            ];

            $form = Form::create([
                'user_id' => Auth::id(),
                'title' => $tpl['title'],
                'description' => $tpl['description'],
                'status' => 'draft',
                'schema' => $schema,
                'share_token' => Str::random(32),
            ]);

            FormVersion::create([
                'form_id' => $form->id,
                'version_number' => 1,
                'schema' => $schema,
                'created_by' => Auth::id(),
            ]);

            \App\Models\ActivityLog::create([
                'user_id' => Auth::id(),
                'form_id' => $form->id,
                'action' => 'created',
                'description' => "Created form from template: {$tpl['title']}.",
                'ip_address' => request()->ip() ?? '127.0.0.1',
            ]);

            return $form;
        });

        return redirect()->route('forms.edit', $form->id)
            ->with('toast_success', "Form successfully created from '{$tpl['title']}' template!");
    }
}
