<?php

namespace App\Policies;

use App\Domain\Attachments\Models\Attachment;
use App\Models\User;

class AttachmentPolicy extends BasePolicy
{
    public function viewAny(User $user): bool
    {
        return $this->hasPermission($user, 'view attachments');
    }

    public function view(User $user, Attachment $attachment): bool
    {
        return $this->hasPermission($user, 'view attachments');
    }

    public function download(User $user, Attachment $attachment): bool
    {
        return $this->hasPermission($user, 'download attachments');
    }

    public function create(User $user): bool
    {
        return $this->hasPermission($user, 'upload attachments');
    }

    public function delete(User $user, Attachment $attachment): bool
    {
        return $this->hasPermission($user, 'delete attachments');
    }
}
