<?php

namespace App\Enums;

enum PrinterConnectionType: string
{
    case Lan = 'lan';
    case Usb = 'usb';

    public function label(): string
    {
        return match ($this) {
            self::Lan => 'LAN',
            self::Usb => 'USB',
        };
    }
}
