<?php

namespace App\Policies;

use App\Domain\Accessories\Models\Accessory;
use App\Models\User;

class AccessoryPolicy extends BasePolicy
{
    public function viewAny(User $user): bool
    {
        return $this->hasPermission($user, 'view accessories');
    }

    public function view(User $user, Accessory $accessory): bool
    {
        return $this->hasPermission($user, 'view accessories');
    }

    public function create(User $user): bool
    {
        return $this->hasPermission($user, 'create accessories');
    }

    public function update(User $user, Accessory $accessory): bool
    {
        return $this->hasPermission($user, 'update accessories');
    }

    public function delete(User $user, Accessory $accessory): bool
    {
        return $this->hasPermission($user, 'delete accessories');
    }

    public function checkout(User $user, Accessory $accessory): bool
    {
        return $this->hasPermission($user, 'checkout accessories');
    }

    public function checkin(User $user, Accessory $accessory): bool
    {
        return $this->hasPermission($user, 'checkin accessories');
    }
}
