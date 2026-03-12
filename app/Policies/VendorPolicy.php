<?php

namespace App\Policies;

use App\Domain\Vendors\Models\Vendor;
use App\Models\User;

class VendorPolicy extends BasePolicy
{
    public function viewAny(User $user): bool
    {
        return $this->hasPermission($user, 'view vendors');
    }

    public function view(User $user, Vendor $vendor): bool
    {
        return $this->hasPermission($user, 'view vendors');
    }

    public function create(User $user): bool
    {
        return $this->hasPermission($user, 'create vendors');
    }

    public function update(User $user, Vendor $vendor): bool
    {
        return $this->hasPermission($user, 'update vendors');
    }

    public function delete(User $user, Vendor $vendor): bool
    {
        return $this->hasPermission($user, 'delete vendors');
    }
}
