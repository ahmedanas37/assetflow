<?php

namespace App\Policies;

use App\Domain\Assets\Models\Asset;
use App\Models\User;

class AssetPolicy extends BasePolicy
{
    public function viewAny(User $user): bool
    {
        return $this->hasPermission($user, 'view assets');
    }

    public function view(User $user, Asset $asset): bool
    {
        return $this->hasPermission($user, 'view assets');
    }

    public function create(User $user): bool
    {
        return $this->hasPermission($user, 'create assets');
    }

    public function update(User $user, Asset $asset): bool
    {
        return $this->hasPermission($user, 'update assets');
    }

    public function delete(User $user, Asset $asset): bool
    {
        return $this->hasPermission($user, 'delete assets');
    }

    public function restore(User $user, Asset $asset): bool
    {
        return $this->hasPermission($user, 'restore assets');
    }

    public function checkout(User $user, Asset $asset): bool
    {
        return $this->hasPermission($user, 'checkout assets');
    }

    public function checkin(User $user, Asset $asset): bool
    {
        return $this->hasPermission($user, 'checkin assets');
    }

    public function export(User $user): bool
    {
        return $this->hasPermission($user, 'export assets');
    }

    public function import(User $user): bool
    {
        return $this->hasPermission($user, 'import assets');
    }

    public function printLabel(User $user): bool
    {
        return $this->hasPermission($user, 'print labels');
    }
}
