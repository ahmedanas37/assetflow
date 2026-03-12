<?php

namespace App\Policies;

use App\Domain\Assets\Models\AssetAssignment;
use App\Models\User;

class AssetAssignmentPolicy extends BasePolicy
{
    public function viewAny(User $user): bool
    {
        return $this->hasPermission($user, 'view assignments');
    }

    public function view(User $user, AssetAssignment $assignment): bool
    {
        return $this->hasPermission($user, 'view assignments');
    }

    public function create(User $user): bool
    {
        return $this->hasPermission($user, 'create assignments');
    }

    public function update(User $user, AssetAssignment $assignment): bool
    {
        return $this->hasPermission($user, 'update assignments');
    }

    public function delete(User $user, AssetAssignment $assignment): bool
    {
        return $this->hasPermission($user, 'delete assignments');
    }
}
