<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class School extends Model
{
    protected $fillable = ['name', 'institution_code', 'address', 'timezone', 'label_width_mm', 'label_height_mm'];

    public static function timezone(): string
    {
        return app('inventory.school')?->timezone ?? 'Asia/Jakarta';
    }

    public static function today(): string
    {
        return now(static::timezone())->toDateString();
    }
}
