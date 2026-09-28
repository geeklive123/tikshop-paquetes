<?php

namespace App\Printing;

use LogicException;

class LanEscPosPrintDriver implements PrintDriverInterface
{
    public function print(array $job): PrintResult
    {
        throw new LogicException('El driver LAN/ESC-POS no está disponible en modo simulación.');
    }
}
