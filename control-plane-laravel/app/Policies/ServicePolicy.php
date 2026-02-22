<?php

namespace App\Policies;

use App\Models\Service;
use App\Models\User;

class ServicePolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Service $service): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return in_array($user->role->name, ['Admin', 'Designer']);
    }

    public function update(User $user, Service $service): bool
    {
        return in_array($user->role->name, ['Admin', 'Designer']);
    }

    public function delete(User $user, Service $service): bool
    {
        return $user->role->name === 'Admin';
    }
}
