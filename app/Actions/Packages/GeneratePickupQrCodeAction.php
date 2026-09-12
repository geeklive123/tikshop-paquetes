<?php

namespace App\Actions\Packages;

use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\PngWriter;
use Endroid\QrCode\Writer\SvgWriter;

class GeneratePickupQrCodeAction
{
    public function execute(string $url): string
    {
        return (new SvgWriter)->write($this->createQrCode($url))->getDataUri();
    }

    public function executePng(string $url): string
    {
        return (new PngWriter)->write(
            $this->createQrCode($url),
            options: [PngWriter::WRITER_OPTION_NUMBER_OF_COLORS => null],
        )->getString();
    }

    private function createQrCode(string $url): QrCode
    {
        return new QrCode(
            data: $url,
            errorCorrectionLevel: ErrorCorrectionLevel::Medium,
            size: 280,
            margin: 12,
        );
    }
}
