<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\Cylinder;
use App\Models\User;

class CylinderPolicy
{
    public function viewAny(User $user): bool
    {
        return in_array($user->role, [UserRole::Admin, UserRole::Operator], true);
    }

    public function view(User $user, Cylinder $cylinder): bool
    {
        return in_array($user->role, [UserRole::Admin, UserRole::Operator], true);
    }

    public function create(User $user): bool
    {
        return $user->role === UserRole::Admin;
    }

    public function update(User $user, Cylinder $cylinder): bool
    {
        return $user->role === UserRole::Admin;
    }

    public function delete(User $user, Cylinder $cylinder): bool
    {
        return $user->role === UserRole::Admin;
    }
}
