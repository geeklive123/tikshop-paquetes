<?php

namespace App\Printing;

use LogicException;

class WindowsUsbPrintDriver implements PrintDriverInterface
{
    public function print(array $job): PrintResult
    {
        throw new LogicException('El driver USB de Windows no está disponible en modo simulación.');
    }
}
