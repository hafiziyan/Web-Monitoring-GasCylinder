<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\Supplier;
use App\Models\User;

class SupplierPolicy
{
    public function viewAny(User $user): bool
    {
        return in_array($user->role, [UserRole::Admin, UserRole::Operator], true);
    }

    public function view(User $user, Supplier $supplier): bool
    {
        return in_array($user->role, [UserRole::Admin, UserRole::Operator], true);
    }

    public function create(User $user): bool
    {
        return $user->role === UserRole::Admin;
    }

    public function update(User $user, Supplier $supplier): bool
    {
        return $user->role === UserRole::Admin;
    }

    public function delete(User $user, Supplier $supplier): bool
    {
        return $user->role === UserRole::Admin;
    }
}
