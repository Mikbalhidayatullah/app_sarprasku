<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Maintenance extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return ['performed_on' => 'date', 'cost' => 'decimal:2'];
    }

    public function asset()
    {
        return $this->belongsTo(Asset::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
