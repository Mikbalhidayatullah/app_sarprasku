<?php

namespace App\Policies;

use App\Models\Transfer;
use App\Models\User;

class TransferPolicy
{
    public function view(User $u, Transfer $t): bool
    {
        return $u->can('view', $t->asset) && ($u->hasPermission('transfers.create') || $u->hasPermission('transfers.approve') || $u->hasPermission('reports.view'));
    }

    public function submit(User $u, Transfer $t): bool
    {
        return $u->can('view', $t->asset) && $u->hasPermission('transfers.create') && ($t->requested_by === $u->id || $u->role?->slug === 'administrator');
    }

    public function cancel(User $u, Transfer $t): bool
    {
        return $this->submit($u, $t);
    }

    public function approve(User $u, Transfer $t): bool
    {
        return $u->can('view', $t->asset) && $u->hasPermission('transfers.approve') && $t->requested_by !== $u->id;
    }
}
