<?php

namespace App\Actions\Packages;

class BuildWhatsAppPickupShareUrlAction
{
    public const string MESSAGE = 'Hola, tienes un paquete listo para recoger en Tik Shop. Presenta tu QR al momento de recogerlo.';

    public function execute(string $recipientPhone): string
    {
        $digits = preg_replace('/\D+/', '', $recipientPhone) ?? '';

        if (str_starts_with($digits, '00')) {
            $digits = substr($digits, 2);
        }

        if (strlen($digits) === 9 && str_starts_with($digits, '0')) {
            $digits = substr($digits, 1);
        }

        if (strlen($digits) === 8) {
            $digits = '591'.$digits;
        }

        return 'https://wa.me/'.$digits.'?text='.rawurlencode(self::MESSAGE);
    }
}
