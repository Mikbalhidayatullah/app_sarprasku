<?php

namespace App\Services;

use App\Models\Asset;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\PngWriter;

class LabelService
{
    public function qr(Asset $a): string
    {
        $url = rtrim(config('app.url'), '/').'/q/'.$a->qr_token;

        return (new PngWriter)->write(new QrCode(data: $url, errorCorrectionLevel: ErrorCorrectionLevel::Medium, size: 400, margin: 48))->getDataUri();
    }
}
