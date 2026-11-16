<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FundingAllocation extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return ['amount' => 'decimal:2'];
    }

    public function fundSource()
    {
        return $this->belongsTo(FundSource::class);
    }
}
