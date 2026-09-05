<?php

namespace App\Enums;

enum PackageStatus: string
{
    case Received = 'received';
    case ReadyForPickup = 'ready_for_pickup';
    case Delivered = 'delivered';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Received => 'Recibido',
            self::ReadyForPickup => 'Listo para recoger',
            self::Delivered => 'Entregado',
            self::Cancelled => 'Cancelado',
        };
    }
}
