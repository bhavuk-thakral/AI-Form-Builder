<?php

namespace App\Services;

use App\Models\Form;
use App\Models\FormVersion;
use App\Models\ActivityLog;
use App\Repositories\FormRepositoryInterface;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

class FormBuilderService
{
    protected $formRepository;

    /**
     * Inject Form Repository.
     */
    public function __construct(FormRepositoryInterface $formRepository)
    {
        $this->formRepository = $formRepository;
    }

    /**
     * Retrieve all active forms for a user.
     */
    public function getFormsForUser(int $userId)
    {
        return $this->formRepository->allForUser($userId);
    }

    /**
     * Retrieve a specific form for a user.
     */
    public function getFormByIdForUser(int $formId, int $userId): ?Form
    {
        return $this->formRepository->findByIdForUser($formId, $userId);
    }

    /**
     * Find a form by its public token slug.
     */
    public function getFormByShareToken(string $token): ?Form
    {
        return $this->formRepository->findByShareToken($token);
    }

    /**
     * Create a new form with default schema and logs.
     */
    public function createForm(array $data, int $userId): Form
    {
        return DB::transaction(function () use ($data, $userId) {
            $formData = array_merge($data, [
                'user_id' => $userId,
                'share_token' => Str::random(32),
                'status' => $data['status'] ?? 'draft',
                'schema' => $data['schema'] ?? [
                    'title' => $data['title'],
                    'description' => $data['description'] ?? '',
                    'fields' => []
                ]
            ]);

            // Save form
            $form = $this->formRepository->create($formData);

            // Log Form Version 1
            FormVersion::create([
                'form_id' => $form->id,
                'version_number' => 1,
                'schema' => $form->schema,
                'description' => 'Initial Form Creation',
                'created_by' => $userId,
            ]);

            // Log activity
            ActivityLog::create([
                'user_id' => $userId,
                'form_id' => $form->id,
                'action' => 'created',
                'description' => "Form '{$form->title}' created.",
                'ip_address' => request()->ip(),
            ]);

            return $form;
        });
    }

    /**
     * Update form details.
     */
    public function updateForm(Form $form, array $data, int $userId): Form
    {
        return DB::transaction(function () use ($form, $data, $userId) {
            // Check if title or schema changed, then update
            $updatedForm = $this->formRepository->update($form, $data);

            // Log update activity
            ActivityLog::create([
                'user_id' => $userId,
                'form_id' => $form->id,
                'action' => 'updated',
                'description' => "Form '{$form->title}' settings updated.",
                'ip_address' => request()->ip(),
            ]);

            return $updatedForm;
        });
    }

    /**
     * Delete form.
     */
    public function deleteForm(Form $form, int $userId): bool
    {
        return DB::transaction(function () use ($form, $userId) {
            // Write action log before deleting form (set null on delete keeps record of action)
            ActivityLog::create([
                'user_id' => $userId,
                'form_id' => null, // form is going to be deleted
                'action' => 'deleted',
                'description' => "Form '{$form->title}' (ID: {$form->id}) was deleted.",
                'ip_address' => request()->ip(),
            ]);

            return $this->formRepository->delete($form);
        });
    }

    /**
     * Increment views count.
     */
    public function incrementViews(Form $form): Form
    {
        return $this->formRepository->update($form, [
            'views_count' => $form->views_count + 1
        ]);
    }
}
