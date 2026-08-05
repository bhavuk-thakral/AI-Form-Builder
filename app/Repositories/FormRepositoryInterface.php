<?php

namespace App\Repositories;

use App\Models\Form;

interface FormRepositoryInterface
{
    /**
     * Get all forms belonging to a user.
     */
    public function allForUser(int $userId);

    /**
     * Find a form by ID and user.
     */
    public function findByIdForUser(int $formId, int $userId): ?Form;

    /**
     * Find a form by its public share token.
     */
    public function findByShareToken(string $token): ?Form;

    /**
     * Create a new form record.
     */
    public function create(array $data): Form;

    /**
     * Update an existing form record.
     */
    public function update(Form $form, array $data): Form;

    /**
     * Delete a form record.
     */
    public function delete(Form $form): bool;
}
