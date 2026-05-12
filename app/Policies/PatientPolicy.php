<?php

namespace App\Policies;

use App\Models\User;

class PatientPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isSuperAdmin() || $user->hasPermission('patients.view');
    }

    public function view(User $user): bool
    {
        return $user->isSuperAdmin() || $user->hasPermission('patients.view');
    }

    public function create(User $user): bool
    {
        return $user->isSuperAdmin() || $user->hasPermission('patients.create');
    }

    public function update(User $user): bool
    {
        return $user->isSuperAdmin() || $user->hasPermission('patients.update');
    }

    public function delete(User $user): bool
    {
        return $user->isSuperAdmin() || $user->hasPermission('patients.delete');
    }
}
