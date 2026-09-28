<?php

namespace App\Enums;

enum ReportPeriod: string
{
    case Today = 'today';
    case ThisWeek = 'this_week';
    case ThisMonth = 'this_month';
    case Custom = 'custom';

    public function label(): string
    {
        return match ($this) {
            self::Today => 'Hoy',
            self::ThisWeek => 'Esta semana',
            self::ThisMonth => 'Este mes',
            self::Custom => 'Rango personalizado',
        };
    }
}
