<?php

namespace App\Policies;

use App\Models\Schedule;
use App\Models\User;

class SchedulePolicy
{
    public function viewAny(User $user): bool
    {
        return in_array($user->role->name, ['Admin', 'Designer']);
    }

    public function view(User $user, Schedule $schedule): bool
    {
        return in_array($user->role->name, ['Admin', 'Designer']);
    }

    public function create(User $user): bool
    {
        return in_array($user->role->name, ['Admin', 'Designer']);
    }

    public function update(User $user, Schedule $schedule): bool
    {
        return in_array($user->role->name, ['Admin', 'Designer']);
    }

    public function delete(User $user, Schedule $schedule): bool
    {
        return $user->role->name === 'Admin';
    }
}
