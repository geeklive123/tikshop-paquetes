<?php

namespace App\Enums;

enum SellerDocumentType: string
{
    case Ci = 'ci';
    case Nit = 'nit';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::Ci => 'CI',
            self::Nit => 'NIT',
            self::Other => 'Otro',
        };
    }
}
