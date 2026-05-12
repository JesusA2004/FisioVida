<?php

namespace App\Policies;

use App\Models\User;

class FilePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isSuperAdmin() || $user->hasPermission('files.view');
    }

    public function view(User $user): bool
    {
        return $user->isSuperAdmin() || $user->hasPermission('files.view');
    }

    public function download(User $user): bool
    {
        return $user->isSuperAdmin() || $user->hasPermission('files.view');
    }

    public function upload(User $user): bool
    {
        return $user->isSuperAdmin() || $user->hasPermission('files.upload');
    }

    public function update(User $user): bool
    {
        return $user->isSuperAdmin() || $user->hasPermission('files.upload');
    }

    public function delete(User $user): bool
    {
        return $user->isSuperAdmin() || $user->hasPermission('files.delete');
    }
}
