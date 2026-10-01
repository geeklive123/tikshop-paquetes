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
            size: 320,
            margin: 12,
        );

        return $this->composeImage($package->tracking_code, $qrPng);
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

    private function composeImage(string $trackingCode, string $qrPng): string
    {
        $canvas = imagecreatetruecolor(450, 560);
        $qrImage = imagecreatefromstring($qrPng);

        if ($canvas === false || $qrImage === false) {
            throw new \RuntimeException('No se pudo componer la imagen QR compartible.');
        }

        $white = imagecolorallocate($canvas, 255, 255, 255);
        $black = imagecolorallocate($canvas, 17, 17, 17);
        $gray = imagecolorallocate($canvas, 75, 85, 99);
        $red = imagecolorallocate($canvas, 242, 13, 24);
        imagefill($canvas, 0, 0, $white);
        imagefilledrectangle($canvas, 0, 0, 450, 8, $red);

        $logoDrawn = $this->drawLogo($canvas);

        if (! $logoDrawn) {
            $this->drawCenteredText($canvas, 'TIK SHOP', 5, 38, $red);
        }

        $this->drawCenteredText($canvas, 'CODIGO DE SEGUIMIENTO', 3, 94, $gray);
        $this->drawCenteredText($canvas, $trackingCode, 5, 112, $black);
        imagecopy($canvas, $qrImage, 65, 148, 0, 0, imagesx($qrImage), imagesy($qrImage));
        imagedestroy($qrImage);
        $this->drawCenteredText($canvas, 'Presenta este QR para recoger tu', 4, 490, $black);
        $this->drawCenteredText($canvas, 'paquete en Tik Shop.', 4, 510, $black);

        $shareableImage = imagecreatetruecolor(900, 1120);

        if ($shareableImage === false) {
            imagedestroy($canvas);

            throw new \RuntimeException('No se pudo crear la imagen QR compartible.');
        }

        imagecopyresized($shareableImage, $canvas, 0, 0, 0, 0, 900, 1120, 450, 560);
        imagedestroy($canvas);
        ob_start();
        $written = imagepng($shareableImage, null, 6);
        $png = ob_get_clean();
        imagedestroy($shareableImage);

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

        imagecopyresampled($canvas, $logo, 185, 16, 0, 0, 80, 80, imagesx($logo), imagesy($logo));
        imagedestroy($logo);

        return true;
    }

    private function drawCenteredText(\GdImage $image, string $text, int $font, int $y, int $color): void
    {
        $x = max(0, (int) floor((imagesx($image) - (imagefontwidth($font) * strlen($text))) / 2));
        imagestring($image, $font, $x, $y, $text, $color);
    }
}
