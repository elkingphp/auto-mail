<?php

namespace App\Policies;

use App\Models\Execution;
use App\Models\User;

class ExecutionPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Execution $execution): bool
    {
        return true;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return true; // Any authenticated user can trigger a report
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Execution $execution): bool
    {
        return $user->role->name === 'Admin'; // Only admins should update execution status manually if needed
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Execution $execution): bool
    {
        return $user->role->name === 'Admin';
    }
}
