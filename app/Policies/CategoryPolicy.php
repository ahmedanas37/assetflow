<?php

namespace App\Policies;

use App\Domain\Inventory\Models\Category;
use App\Models\User;

class CategoryPolicy extends BasePolicy
{
    public function viewAny(User $user): bool
    {
        return $this->hasPermission($user, 'view categories');
    }

    public function view(User $user, Category $category): bool
    {
        return $this->hasPermission($user, 'view categories');
    }

    public function create(User $user): bool
    {
        return $this->hasPermission($user, 'create categories');
    }

    public function update(User $user, Category $category): bool
    {
        return $this->hasPermission($user, 'update categories');
    }

    public function delete(User $user, Category $category): bool
    {
        return $this->hasPermission($user, 'delete categories');
    }
}
