<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

abstract class MasterRecord extends Model
{
    protected $fillable = ['name', 'code', 'notes', 'is_active', 'location_id', 'is_bmn'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean', 'is_bmn' => 'boolean'];
    }
}
