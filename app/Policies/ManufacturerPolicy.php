<?php

namespace App\Policies;

use App\Domain\Inventory\Models\Manufacturer;
use App\Models\User;

class ManufacturerPolicy extends BasePolicy
{
    public function viewAny(User $user): bool
    {
        return $this->hasPermission($user, 'view manufacturers');
    }

    public function view(User $user, Manufacturer $manufacturer): bool
    {
        return $this->hasPermission($user, 'view manufacturers');
    }

    public function create(User $user): bool
    {
        return $this->hasPermission($user, 'create manufacturers');
    }

    public function update(User $user, Manufacturer $manufacturer): bool
    {
        return $this->hasPermission($user, 'update manufacturers');
    }

    public function delete(User $user, Manufacturer $manufacturer): bool
    {
        return $this->hasPermission($user, 'delete manufacturers');
    }
}
