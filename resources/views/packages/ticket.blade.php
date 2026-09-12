<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Ticket {{ $package->tracking_code }}</title>
    <style>
        @page { margin: 12px 14px; }
        * { box-sizing: border-box; }
        body { margin: 0; color: #171717; font-family: DejaVu Sans, sans-serif; font-size: 10px; }
        .center { text-align: center; }
        .brand { color: #e5252a; font-size: 22px; font-weight: bold; letter-spacing: .5px; }
        .muted { color: #666; }
        .tracking { margin: 7px 0 2px; font-family: DejaVu Sans Mono, monospace; font-size: 15px; font-weight: bold; }
        .divider { margin: 9px 0; border-top: 1px dashed #888; }
        table { width: 100%; border-collapse: collapse; }
        td { padding: 3px 0; vertical-align: top; }
        td:first-child { width: 42%; color: #666; }
        td:last-child { font-weight: bold; text-align: right; }
        .location { margin: 8px 0; padding: 8px; border: 2px solid #e5252a; text-align: center; }
        .location-code { font-family: DejaVu Sans Mono, monospace; font-size: 22px; font-weight: bold; }
        .description { margin-top: 3px; font-weight: normal; line-height: 1.35; text-align: left; }
        .description-row { text-align: left !important; }
        .price { color: #e5252a; font-size: 14px; }
        .footer { margin-top: 6px; font-size: 8px; line-height: 1.35; }
    </style>
</head>
<body>
    <div class="center">
        <div class="brand">TIK SHOP</div>
        <div>{{ $package->branch->name }}</div>
        @if ($package->branch->address)<div class="muted">{{ $package->branch->address }}</div>@endif
        <div class="tracking">{{ $package->tracking_code }}</div>
        <div class="muted">{{ $package->received_at?->format('d/m/Y H:i') }}</div>
    </div>

    <div class="location">
        <div class="muted">UBICACIÓN DE ALMACENAJE</div>
        <div class="location-code">{{ $package->storage_code }}</div>
        <div>{{ $package->category->name }}</div>
    </div>

    <table>
        <tr><td>Remitente</td><td>{{ $package->sender_name }}</td></tr>
        <tr><td>Destinatario</td><td>{{ $package->recipient_name }}</td></tr>
        <tr><td>Tel. destinatario</td><td>{{ $package->recipient_phone }}</td></tr>
        <tr><td>Estado</td><td>{{ $package->status->label() }}</td></tr>
        <tr><td>Costo almacenaje</td><td class="price">Bs {{ number_format((float) $package->storage_price, 2) }}</td></tr>
        <tr><td colspan="2" class="description-row"><div class="divider"></div><span class="muted">Descripción</span><div class="description">{{ $package->description ?: 'Sin descripción' }}</div></td></tr>
    </table>

    <div class="divider"></div>
    <div class="center">
        @if (! empty($qrImagePath))
            <div style="text-align: center; margin-top: 10px;">
                <img
                    src="{{ $qrImagePath }}"
                    width="130"
                    height="130"
                    alt="QR de recojo"
                >
            </div>
        @endif
        <div class="footer">Presenta este QR al recoger el paquete.<br>El operador verificará los datos antes de confirmar la entrega.<br>El QR deja de funcionar después de la entrega.</div>
    </div>
</body>
</html>
