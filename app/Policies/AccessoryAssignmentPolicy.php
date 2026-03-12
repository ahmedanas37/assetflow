<?php

namespace App\Policies;

use App\Domain\Accessories\Models\AccessoryAssignment;
use App\Models\User;

class AccessoryAssignmentPolicy extends BasePolicy
{
    public function viewAny(User $user): bool
    {
        return $this->hasPermission($user, 'view accessory assignments');
    }

    public function view(User $user, AccessoryAssignment $assignment): bool
    {
        return $this->hasPermission($user, 'view accessory assignments');
    }
}
