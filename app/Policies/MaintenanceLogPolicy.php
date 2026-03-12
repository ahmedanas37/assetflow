<?php

namespace App\Policies;

use App\Domain\Maintenance\Models\MaintenanceLog;
use App\Models\User;

class MaintenanceLogPolicy extends BasePolicy
{
    public function viewAny(User $user): bool
    {
        return $this->hasPermission($user, 'view maintenance');
    }

    public function view(User $user, MaintenanceLog $log): bool
    {
        return $this->hasPermission($user, 'view maintenance');
    }

    public function create(User $user): bool
    {
        return $this->hasPermission($user, 'create maintenance');
    }

    public function update(User $user, MaintenanceLog $log): bool
    {
        return $this->hasPermission($user, 'update maintenance');
    }

    public function delete(User $user, MaintenanceLog $log): bool
    {
        return $this->hasPermission($user, 'delete maintenance');
    }

    public function close(User $user, MaintenanceLog $log): bool
    {
        return $this->hasPermission($user, 'close maintenance');
    }
}
