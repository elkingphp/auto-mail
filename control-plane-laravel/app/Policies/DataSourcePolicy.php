<?php

namespace App\Policies;

use App\Models\DataSource;
use App\Models\User;

class DataSourcePolicy
{
    public function viewAny(User $user): bool
    {
        return in_array($user->role->name, ['Admin', 'Designer']);
    }

    public function view(User $user, DataSource $dataSource): bool
    {
        return in_array($user->role->name, ['Admin', 'Designer']);
    }

    public function create(User $user): bool
    {
        return in_array($user->role->name, ['Admin', 'Designer']);
    }

    public function update(User $user, DataSource $dataSource): bool
    {
        return in_array($user->role->name, ['Admin', 'Designer']);
    }

    public function delete(User $user, DataSource $dataSource): bool
    {
        return $user->role->name === 'Admin';
    }

    public function schema(User $user, DataSource $dataSource): bool
    {
        return in_array($user->role->name, ['Admin', 'Designer']);
    }

    public function testConnection(User $user, DataSource $dataSource): bool
    {
        return in_array($user->role->name, ['Admin', 'Designer']);
    }
}
