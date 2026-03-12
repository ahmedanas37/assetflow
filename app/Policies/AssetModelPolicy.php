<?php

namespace App\Policies;

use App\Domain\Inventory\Models\AssetModel;
use App\Models\User;

class AssetModelPolicy extends BasePolicy
{
    public function viewAny(User $user): bool
    {
        return $this->hasPermission($user, 'view asset models');
    }

    public function view(User $user, AssetModel $model): bool
    {
        return $this->hasPermission($user, 'view asset models');
    }

    public function create(User $user): bool
    {
        return $this->hasPermission($user, 'create asset models');
    }

    public function update(User $user, AssetModel $model): bool
    {
        return $this->hasPermission($user, 'update asset models');
    }

    public function delete(User $user, AssetModel $model): bool
    {
        return $this->hasPermission($user, 'delete asset models');
    }

    public function restore(User $user, AssetModel $model): bool
    {
        return $this->hasPermission($user, 'delete asset models');
    }
}
