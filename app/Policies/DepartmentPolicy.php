<?php

namespace App\Policies;

use App\Domain\People\Models\Department;
use App\Models\User;

class DepartmentPolicy extends BasePolicy
{
    public function viewAny(User $user): bool
    {
        return $this->hasPermission($user, 'view departments');
    }

    public function view(User $user, Department $department): bool
    {
        return $this->hasPermission($user, 'view departments');
    }

    public function create(User $user): bool
    {
        return $this->hasPermission($user, 'create departments');
    }

    public function update(User $user, Department $department): bool
    {
        return $this->hasPermission($user, 'update departments');
    }

    public function delete(User $user, Department $department): bool
    {
        return $this->hasPermission($user, 'delete departments');
    }
}
