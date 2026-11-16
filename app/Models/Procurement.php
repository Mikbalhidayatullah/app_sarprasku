<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Procurement extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return ['total' => 'decimal:2', 'acquired_on' => 'date'];
    }

    public function items()
    {
        return $this->hasMany(ProcurementItem::class);
    }

    public function allocations()
    {
        return $this->hasMany(FundingAllocation::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
