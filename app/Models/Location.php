<?php

namespace App\Models;

class Location extends MasterRecord
{
    public function rooms()
    {
        return $this->hasMany(Room::class);
    }
}
