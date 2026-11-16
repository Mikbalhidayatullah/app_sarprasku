<?php

namespace App\Services;

use App\Models\Asset;
use App\Models\AssetEvent;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class History
{
    public static function audit(User $actor, string $action, Model $subject, array $changes = []): void
    {
        AuditLog::create(['actor_id' => $actor->id, 'actor_snapshot' => $actor->name, 'action' => $action,
            'subject_type' => $subject->getMorphClass(), 'subject_id' => (string) $subject->getKey(), 'changes' => $changes]);
    }

    public static function event(Asset $asset, User $actor, string $type, array $data, ?string $date = null): void
    {
        AssetEvent::create(['asset_id' => $asset->id, 'actor_id' => $actor->id, 'actor_snapshot' => $actor->name, 'type' => $type, 'data' => $data, 'effective_on' => $date]);
    }
}
