<?php

namespace App\Actions\Packages;

use App\Enums\PackageStatus;
use App\Models\Package;
use App\Models\PackagePickupToken;
use Illuminate\Validation\ValidationException;

class GenerateShareablePickupQrImageAction
{
    public function __construct(
        private GeneratePickupQrCodeAction $generatePickupQrCode,
        private ResolveTicketLogoAction $resolveTicketLogo,
    ) {}

    public function execute(Package $package): string
    {
        $this->ensurePackageCanBeShared($package);

        $pickupToken = $package->pickupTokens()->latest('id')->first();
        $rawToken = $this->usableRawToken($pickupToken);
        $qrPng = $this->generatePickupQrCode->executePng(
            route('pickup.show', ['token' => $rawToken]),
            size: 560,
            margin: 12,
        );

        return $this->composeImage($package, $qrPng);
    }

    private function ensurePackageCanBeShared(Package $package): void
    {
        if ($package->status === PackageStatus::Delivered) {
            throw ValidationException::withMessages([
                'pickup_qr' => 'Este paquete ya fue entregado y su QR no se puede compartir.',
            ]);
        }

        if ($package->status === PackageStatus::Cancelled) {
            throw ValidationException::withMessages([
                'pickup_qr' => 'Este paquete fue anulado y su QR ya no es válido.',
            ]);
        }
    }

    private function usableRawToken(?PackagePickupToken $pickupToken): string
    {
        if ($pickupToken === null) {
            throw ValidationException::withMessages([
                'pickup_qr' => 'El paquete no tiene un QR activo para compartir.',
            ]);
        }

        if ($pickupToken->used_at !== null) {
            throw ValidationException::withMessages([
                'pickup_qr' => 'El QR de este paquete ya fue utilizado.',
            ]);
        }

        if ($pickupToken->revoked_at !== null) {
            throw ValidationException::withMessages([
                'pickup_qr' => 'El QR de este paquete fue revocado.',
            ]);
        }

        if ($pickupToken->expires_at?->isPast()) {
            throw ValidationException::withMessages([
                'pickup_qr' => 'El QR de este paquete ha expirado.',
            ]);
        }

        $rawToken = $pickupToken->token_encrypted;

        if (! is_string($rawToken) || $rawToken === '' || ! hash_equals($pickupToken->token_hash, hash('sha256', $rawToken))) {
            throw ValidationException::withMessages([
                'pickup_qr' => 'El paquete no tiene un QR activo para compartir.',
            ]);
        }

        return $rawToken;
    }

    private function composeImage(Package $package, string $qrPng): string
    {
        $canvas = imagecreatetruecolor(900, 1120);
        $qrImage = imagecreatefromstring($qrPng);

        if ($canvas === false || $qrImage === false) {
            throw new \RuntimeException('No se pudo componer la imagen QR compartible.');
        }

        $white = imagecolorallocate($canvas, 255, 255, 255);
        $black = imagecolorallocate($canvas, 17, 17, 17);
        $gray = imagecolorallocate($canvas, 75, 85, 99);
        $red = imagecolorallocate($canvas, 242, 13, 24);
        imagefill($canvas, 0, 0, $white);
        imagefilledrectangle($canvas, 0, 0, 900, 12, $red);

        $logoDrawn = $this->drawLogo($canvas);

        if (! $logoDrawn) {
            $this->drawCenteredText($canvas, 'TIK SHOP', 42, 110, $red, bold: true);
        }

        $this->drawCenteredText($canvas, 'Código:', 21, 205, $gray, bold: true);
        $this->drawCenteredText($canvas, $package->tracking_code, 34, 244, $black, bold: true);
        $this->drawCenteredText($canvas, 'Destinatario:', 21, 290, $gray, bold: true);
        $this->drawCenteredText($canvas, $package->recipient_name, 32, 329, $black, bold: true);
        $this->drawCenteredText($canvas, 'Celular:', 21, 375, $gray, bold: true);
        $this->drawCenteredText($canvas, $package->recipient_phone, 32, 414, $black, bold: true);

        $qrX = (int) floor((imagesx($canvas) - imagesx($qrImage)) / 2);
        imagecopy($canvas, $qrImage, $qrX, 438, 0, 0, imagesx($qrImage), imagesy($qrImage));
        imagedestroy($qrImage);
        $this->drawCenteredText(
            $canvas,
            'Presenta este QR para recoger tu paquete en Tik Shop.',
            22,
            1050,
            $black,
        );

        ob_start();
        $written = imagepng($canvas, null, 6);
        $png = ob_get_clean();
        imagedestroy($canvas);

        if (! $written || ! is_string($png)) {
            throw new \RuntimeException('No se pudo codificar la imagen QR compartible.');
        }

        return $png;
    }

    private function drawLogo(\GdImage $canvas): bool
    {
        $logoDataUri = $this->resolveTicketLogo->execute();

        if (! is_string($logoDataUri) || ! str_contains($logoDataUri, ',')) {
            return false;
        }

        [, $encodedLogo] = explode(',', $logoDataUri, 2);
        $logoContents = base64_decode($encodedLogo, true);
        $logo = is_string($logoContents) ? imagecreatefromstring($logoContents) : false;

        if ($logo === false) {
            return false;
        }

        imagecopyresampled($canvas, $logo, 375, 25, 0, 0, 150, 150, imagesx($logo), imagesy($logo));
        imagedestroy($logo);

        return true;
    }

    private function drawCenteredText(
        \GdImage $image,
        string $text,
        float $fontSize,
        int $baseline,
        int $color,
        bool $bold = false,
        int $maxWidth = 760,
    ): void {
        $fontPath = base_path('vendor/dompdf/dompdf/lib/fonts/'.($bold ? 'DejaVuSans-Bold.ttf' : 'DejaVuSans.ttf'));

        if (! is_readable($fontPath)) {
            throw new \RuntimeException('No se encontró la tipografía para la imagen QR compartible.');
        }

        $fittedFontSize = $fontSize;
        $boundingBox = imagettfbbox($fittedFontSize, 0, $fontPath, $text);

        while ($boundingBox !== false && ($boundingBox[2] - $boundingBox[0]) > $maxWidth && $fittedFontSize > 18) {
            $fittedFontSize--;
            $boundingBox = imagettfbbox($fittedFontSize, 0, $fontPath, $text);
        }

        if ($boundingBox === false) {
            throw new \RuntimeException('No se pudo medir el texto de la imagen QR compartible.');
        }

        $textWidth = $boundingBox[2] - $boundingBox[0];
        $x = (int) floor((imagesx($image) - $textWidth) / 2) - $boundingBox[0];

        if (imagettftext($image, $fittedFontSize, 0, $x, $baseline, $color, $fontPath, $text) === false) {
            throw new \RuntimeException('No se pudo dibujar el texto de la imagen QR compartible.');
        }
    }
}
