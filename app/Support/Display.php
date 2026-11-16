<?php

namespace App\Support;

use App\Models\School;
use Illuminate\Support\Carbon;

class Display
{
    public static function money($value): string
    {
        if ($value === null) {
            return 'Belum diketahui';
        }
        $parts = explode('.', bcadd((string) $value, '0', 2));

        return 'Rp '.preg_replace('/\B(?=(\d{3})+(?!\d))/', '.', $parts[0]).','.$parts[1];
    }

    public static function date($value, bool $time = false): string
    {
        return $value ? Carbon::parse($value)->timezone(School::timezone())->locale('id')->translatedFormat($time ? 'd M Y, H:i' : 'd M Y') : 'Belum diketahui';
    }

    public static function condition(string $value): string
    {
        return config('inventory.conditions.'.$value, $value);
    }

    public static function status(string $value): string
    {
        return config('inventory.statuses.'.$value, $value);
    }

    public static function csv($value): string
    {
        $text = (string) ($value ?? '');
        if (preg_match('/^[\s\x00-\x1F]*[=+@-]|^[\t\r\n]|^0\d/u', $text)) {
            return "'".$text;
        }

        return $text;
    }
}
