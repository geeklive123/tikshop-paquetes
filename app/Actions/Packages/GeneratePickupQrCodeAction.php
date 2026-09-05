<?php

namespace App\Actions\Packages;

use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\SvgWriter;

class GeneratePickupQrCodeAction
{
    public function execute(string $url): string
    {
        $qrCode = new QrCode(
            data: $url,
            errorCorrectionLevel: ErrorCorrectionLevel::Medium,
            size: 280,
            margin: 12,
        );

        return (new SvgWriter)->write($qrCode)->getDataUri();
    }
}
