<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AssetEvent extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return ['data' => 'array', 'effective_on' => 'date'];
    }

    public function actor()
    {
        return $this->belongsTo(User::class, 'actor_id');
    }
}
