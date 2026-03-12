<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy extends BasePolicy
{
    public function viewAny(User $user): bool
    {
        return $this->hasPermission($user, 'view users');
    }

    public function view(User $user, User $model): bool
    {
        return $this->hasPermission($user, 'view users');
    }

    public function create(User $user): bool
    {
        return $this->hasPermission($user, 'create users');
    }

    public function update(User $user, User $model): bool
    {
        return $this->hasPermission($user, 'update users');
    }

    public function delete(User $user, User $model): bool
    {
        return $this->hasPermission($user, 'delete users');
    }
}
