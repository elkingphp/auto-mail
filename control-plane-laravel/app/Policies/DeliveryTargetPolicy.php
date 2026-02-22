<?php

namespace App\Policies;

use App\Models\DeliveryTarget;
use App\Models\User;

class DeliveryTargetPolicy
{
    public function viewAny(User $user): bool
    {
        return in_array($user->role->name, ['Admin', 'Designer']);
    }

    public function view(User $user, DeliveryTarget $deliveryTarget): bool
    {
        return in_array($user->role->name, ['Admin', 'Designer']);
    }

    public function create(User $user): bool
    {
        return in_array($user->role->name, ['Admin', 'Designer']);
    }

    public function update(User $user, DeliveryTarget $deliveryTarget): bool
    {
        return in_array($user->role->name, ['Admin', 'Designer']);
    }

    public function delete(User $user, DeliveryTarget $deliveryTarget): bool
    {
        return $user->role->name === 'Admin';
    }
}
