<?php

namespace App\Policies;

use App\Domain\Assets\Models\StatusLabel;
use App\Models\User;

class StatusLabelPolicy extends BasePolicy
{
    public function viewAny(User $user): bool
    {
        return $this->hasPermission($user, 'view status labels');
    }

    public function view(User $user, StatusLabel $label): bool
    {
        return $this->hasPermission($user, 'view status labels');
    }

    public function create(User $user): bool
    {
        return $this->hasPermission($user, 'create status labels');
    }

    public function update(User $user, StatusLabel $label): bool
    {
        return $this->hasPermission($user, 'update status labels');
    }

    public function delete(User $user, StatusLabel $label): bool
    {
        return $this->hasPermission($user, 'delete status labels');
    }
}
