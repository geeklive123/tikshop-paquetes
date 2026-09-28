<?php

namespace App\Actions\Packages;

class ResolveTicketLogoAction
{
    public function execute(): ?string
    {
        $logoPath = config('tickets.logo_path', public_path('images/tikshop-logo-ticket.png'));

        if (! is_string($logoPath) || ! is_file($logoPath) || ! is_readable($logoPath)) {
            return null;
        }

        $imageInfo = @getimagesize($logoPath);
        $contents = @file_get_contents($logoPath);

        if ($imageInfo === false || $contents === false) {
            return null;
        }

        $mimeType = $imageInfo['mime'] ?? null;

        if (! is_string($mimeType) || ! in_array($mimeType, ['image/png', 'image/jpeg', 'image/webp'], true)) {
            return null;
        }

        if ($mimeType === 'image/webp') {
            $contents = $this->convertWebpToPng($contents);
            $mimeType = 'image/png';

            if ($contents === null) {
                return null;
            }
        }

        return 'data:'.$mimeType.';base64,'.base64_encode($contents);
    }

    private function convertWebpToPng(string $webpContents): ?string
    {
        if (! function_exists('imagecreatefromstring')) {
            return null;
        }

        $image = @imagecreatefromstring($webpContents);

        if ($image === false) {
            $image = $this->firstAnimatedWebpFrame($webpContents);
        }

        return $image === null || $image === false ? null : $this->pngFromImage($image);
    }

    private function firstAnimatedWebpFrame(string $webpContents): ?\GdImage
    {
        $canvasChunk = $this->findWebpChunk($webpContents, 'VP8X');
        $frameChunk = $this->findWebpChunk($webpContents, 'ANMF');

        if ($canvasChunk === null || strlen($canvasChunk) < 10 || $frameChunk === null || strlen($frameChunk) < 17) {
            return null;
        }

        $canvasWidth = $this->readUint24($canvasChunk, 4) + 1;
        $canvasHeight = $this->readUint24($canvasChunk, 7) + 1;
        $frameX = $this->readUint24($frameChunk, 0) * 2;
        $frameY = $this->readUint24($frameChunk, 3) * 2;
        $frameWidth = $this->readUint24($frameChunk, 6) + 1;
        $frameHeight = $this->readUint24($frameChunk, 9) + 1;
        $framePayload = substr($frameChunk, 16);
        $extendedHeader = chr(str_contains($framePayload, 'ALPH') ? 0x10 : 0x00)
            ."\0\0\0"
            .$this->writeUint24($frameWidth - 1)
            .$this->writeUint24($frameHeight - 1);
        $staticPayload = 'WEBP'.'VP8X'.pack('V', 10).$extendedHeader.$framePayload;
        $frameImage = @imagecreatefromstring('RIFF'.pack('V', strlen($staticPayload)).$staticPayload);

        if ($frameImage === false) {
            return null;
        }

        $canvas = imagecreatetruecolor($canvasWidth, $canvasHeight);

        if ($canvas === false) {
            imagedestroy($frameImage);

            return null;
        }

        imagealphablending($canvas, false);
        imagesavealpha($canvas, true);
        $transparent = imagecolorallocatealpha($canvas, 0, 0, 0, 127);
        imagefill($canvas, 0, 0, $transparent);
        imagecopy($canvas, $frameImage, $frameX, $frameY, 0, 0, $frameWidth, $frameHeight);
        imagedestroy($frameImage);

        return $canvas;
    }

    private function findWebpChunk(string $webpContents, string $chunkName): ?string
    {
        $offset = 12;
        $length = strlen($webpContents);

        while ($offset + 8 <= $length) {
            $name = substr($webpContents, $offset, 4);
            $size = unpack('Vsize', substr($webpContents, $offset + 4, 4))['size'];
            $dataOffset = $offset + 8;

            if ($dataOffset + $size > $length) {
                return null;
            }

            if ($name === $chunkName) {
                return substr($webpContents, $dataOffset, $size);
            }

            $offset = $dataOffset + $size + ($size % 2);
        }

        return null;
    }

    private function readUint24(string $contents, int $offset): int
    {
        return unpack('Vvalue', substr($contents, $offset, 3)."\0")['value'];
    }

    private function writeUint24(int $value): string
    {
        return substr(pack('V', $value), 0, 3);
    }

    private function pngFromImage(\GdImage $image): ?string
    {
        $width = imagesx($image);
        $height = imagesy($image);
        $scale = min(1, 320 / max($width, $height));

        if ($scale < 1) {
            $resizedWidth = max(1, (int) round($width * $scale));
            $resizedHeight = max(1, (int) round($height * $scale));
            $resizedImage = imagecreatetruecolor($resizedWidth, $resizedHeight);

            if ($resizedImage === false) {
                imagedestroy($image);

                return null;
            }

            imagealphablending($resizedImage, false);
            imagesavealpha($resizedImage, true);
            $transparent = imagecolorallocatealpha($resizedImage, 0, 0, 0, 127);
            imagefill($resizedImage, 0, 0, $transparent);
            imagecopyresampled($resizedImage, $image, 0, 0, 0, 0, $resizedWidth, $resizedHeight, $width, $height);
            imagedestroy($image);
            $image = $resizedImage;
        }

        ob_start();
        $converted = imagepng($image, null, 6);
        $pngContents = ob_get_clean();
        imagedestroy($image);

        return $converted && is_string($pngContents) ? $pngContents : null;
    }
}
