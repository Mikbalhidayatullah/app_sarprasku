<?php

namespace App\Policies;

use App\Models\Asset;
use App\Models\School;
use App\Models\User;

class AssetPolicy
{
    public function view(User $u, Asset $a): bool
    {
        return $a->school_id === School::query()->value('id') && $u->hasPermission('assets.view');
    }

    public function update(User $u, Asset $a): bool
    {
        return $this->view($u, $a) && $u->hasPermission('assets.update');
    }

    public function print(User $u, Asset $a): bool
    {
        return $this->view($u, $a) && $u->hasPermission('labels.print');
    }

    public function attach(User $u, Asset $a): bool
    {
        return $this->view($u, $a) && $u->hasPermission('attachments.upload');
    }

    public function archive(User $u, Asset $a): bool
    {
        return $this->view($u, $a) && $u->hasPermission('assets.archive');
    }

    public function dispose(User $u, Asset $a): bool
    {
        return $this->view($u, $a) && $u->hasPermission('assets.dispose');
    }
}
