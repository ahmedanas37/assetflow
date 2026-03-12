<?php

namespace App\Policies;

use App\Domain\People\Models\Employee;
use App\Models\User;

class EmployeePolicy extends BasePolicy
{
    public function viewAny(User $user): bool
    {
        return $this->hasPermission($user, 'view employees');
    }

    public function view(User $user, Employee $employee): bool
    {
        return $this->hasPermission($user, 'view employees');
    }

    public function create(User $user): bool
    {
        return $this->hasPermission($user, 'create employees');
    }

    public function update(User $user, Employee $employee): bool
    {
        return $this->hasPermission($user, 'update employees');
    }

    public function delete(User $user, Employee $employee): bool
    {
        return $this->hasPermission($user, 'delete employees');
    }

    public function import(User $user): bool
    {
        return $this->hasPermission($user, 'import employees');
    }
}
