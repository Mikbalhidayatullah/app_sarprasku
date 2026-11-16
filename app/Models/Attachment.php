<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Attachment extends Model
{
    protected $guarded = [];

    protected $hidden = ['path'];

    public function asset()
    {
        return $this->belongsTo(Asset::class);
    }
}
