<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\GasType;
use App\Models\User;

class GasTypePolicy
{
    public function viewAny(User $user): bool
    {
        return in_array($user->role, [UserRole::Admin, UserRole::Operator], true);
    }

    public function view(User $user, GasType $gasType): bool
    {
        return in_array($user->role, [UserRole::Admin, UserRole::Operator], true);
    }

    public function create(User $user): bool
    {
        return $user->role === UserRole::Admin;
    }

    public function update(User $user, GasType $gasType): bool
    {
        return $user->role === UserRole::Admin;
    }

    public function delete(User $user, GasType $gasType): bool
    {
        return $user->role === UserRole::Admin;
    }
}
