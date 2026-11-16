<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Transfer extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return ['origin_snapshot' => 'array', 'destination_snapshot' => 'array', 'effective_on' => 'date', 'submitted_at' => 'datetime', 'decided_at' => 'datetime', 'cancelled_at' => 'datetime'];
    }

    public function asset()
    {
        return $this->belongsTo(Asset::class);
    }

    public function requester()
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function decider()
    {
        return $this->belongsTo(User::class, 'decided_by');
    }

    public function attachments()
    {
        return $this->hasMany(Attachment::class);
    }
}
