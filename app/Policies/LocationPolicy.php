<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\Location;
use App\Models\User;

class LocationPolicy
{
    public function viewAny(User $user): bool
    {
        return in_array($user->role, [UserRole::Admin, UserRole::Operator], true);
    }

    public function view(User $user, Location $location): bool
    {
        return in_array($user->role, [UserRole::Admin, UserRole::Operator], true);
    }

    public function create(User $user): bool
    {
        return $user->role === UserRole::Admin;
    }

    public function update(User $user, Location $location): bool
    {
        return $user->role === UserRole::Admin;
    }

    public function delete(User $user, Location $location): bool
    {
        return $user->role === UserRole::Admin;
    }
}
