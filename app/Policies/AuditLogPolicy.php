<?php

namespace App\Policies;

use App\Domain\Audits\Models\AuditLog;
use App\Models\User;

class AuditLogPolicy extends BasePolicy
{
    public function viewAny(User $user): bool
    {
        return $this->hasPermission($user, 'view audit logs');
    }

    public function view(User $user, AuditLog $log): bool
    {
        return $this->hasPermission($user, 'view audit logs');
    }
}
