<?php

namespace App\Policies;

use App\Models\ReportFilter;
use App\Models\User;

class ReportFilterPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, ReportFilter $reportFilter): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, ReportFilter $reportFilter): bool
    {
        return true;
    }

    public function delete(User $user, ReportFilter $reportFilter): bool
    {
        return true;
    }
}
