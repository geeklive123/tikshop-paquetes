<?php

namespace App\Enums;

enum PrintJobType: string
{
    case PackageTicket = 'package_ticket';

    public function label(): string
    {
        return match ($this) {
            self::PackageTicket => 'Ticket de paquete',
        };
    }
}
