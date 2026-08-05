<?php

namespace App\Repositories;

use App\Models\Form;

class FormRepository implements FormRepositoryInterface
{
    /**
     * Get all forms belonging to a user.
     */
    public function allForUser(int $userId)
    {
        return Form::where('user_id', $userId)->latest()->get();
    }

    /**
     * Find a form by ID and user.
     */
    public function findByIdForUser(int $formId, int $userId): ?Form
    {
        return Form::where('id', $formId)->where('user_id', $userId)->first();
    }

    /**
     * Find a form by its public share token.
     */
    public function findByShareToken(string $token): ?Form
    {
        return Form::where('share_token', $token)->first();
    }

    /**
     * Create a new form record.
     */
    public function create(array $data): Form
    {
        return Form::create($data);
    }

    /**
     * Update an existing form record.
     */
    public function update(Form $form, array $data): Form
    {
        $form->update($data);
        return $form;
    }

    /**
     * Delete a form record.
     */
    public function delete(Form $form): bool
    {
        return $form->delete();
    }
}
