<?php

namespace App\Policies;

use App\Models\Attachment;
use App\Models\User;

class AttachmentPolicy
{
    public function view(User $u, Attachment $a): bool
    {
        return $u->can('view', $a->asset) && $u->hasPermission('attachments.view');
    }
}
