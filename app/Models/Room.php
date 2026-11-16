<?php

namespace App\Models;

class Room extends MasterRecord
{
    public function location()
    {
        return $this->belongsTo(Location::class);
    }

    public function assets()
    {
        return $this->hasMany(Asset::class);
    }
}
