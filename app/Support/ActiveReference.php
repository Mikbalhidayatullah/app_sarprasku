<?php

namespace App\Support;

use Illuminate\Validation\Rule;

class ActiveReference
{
    public static function rule(string $table, ?int $current = null)
    {
        return Rule::exists($table, 'id')->where(fn ($q) => $q->where('is_active', true)->when($current, fn ($q) => $q->orWhere('id', $current)));
    }
}
