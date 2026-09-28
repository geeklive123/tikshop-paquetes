<?php

namespace App\Actions\Printers;

use App\Enums\PrinterConnectionType;
use App\Models\Printer;

class TestPrinterConnectionAction
{
    public function execute(Printer $printer): string
    {
        if (! $printer->active) {
            return 'La impresora está inactiva. Actívala antes de probar su configuración.';
        }

        if (! $this->hasValidConfiguration($printer)) {
            return 'La configuración de la impresora no es válida. Revisa la conexión, el puerto y el ancho de papel.';
        }

        if ($printer->connection_type === PrinterConnectionType::Lan && $this->isPrivateOrReservedIp($printer->ip_address)) {
            return 'La prueba real de conectividad requiere el agente local de impresión.';
        }

        return 'La configuración es válida. La prueba real se realizará mediante el agente local de impresión.';
    }

    private function hasValidConfiguration(Printer $printer): bool
    {
        if (trim($printer->name) === '' || ! in_array($printer->paper_width, [58, 80], true)) {
            return false;
        }

        if ($printer->connection_type === PrinterConnectionType::Usb) {
            return true;
        }

        return is_string($printer->ip_address)
            && filter_var($printer->ip_address, FILTER_VALIDATE_IP) !== false
            && is_int($printer->port)
            && $printer->port >= 1
            && $printer->port <= 65535;
    }

    private function isPrivateOrReservedIp(?string $ipAddress): bool
    {
        if ($ipAddress === null) {
            return false;
        }

        return filter_var(
            $ipAddress,
            FILTER_VALIDATE_IP,
            FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE,
        ) === false;
    }
}
