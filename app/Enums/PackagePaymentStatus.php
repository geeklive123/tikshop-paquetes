<?php

namespace App\Enums;

enum PackagePaymentStatus: string
{
    case Pending = 'pending';
    case Paid = 'paid';
    case Legacy = 'legacy';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'POR PAGAR',
            self::Paid => 'PAGADO',
            self::Legacy => 'SIN SEGUIMIENTO HISTÓRICO',
        };
    }
}
