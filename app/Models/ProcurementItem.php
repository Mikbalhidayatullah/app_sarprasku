<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProcurementItem extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return ['unit_price' => 'decimal:2'];
    }

    public function procurement()
    {
        return $this->belongsTo(Procurement::class);
    }

    public function assets()
    {
        return $this->hasMany(Asset::class);
    }
}
