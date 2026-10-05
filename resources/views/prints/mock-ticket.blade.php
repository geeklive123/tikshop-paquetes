<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Ticket {{ $payload['tracking_code'] ?? '' }}</title>
    <style>
        @page { size: 80mm auto; margin: 4mm; }
        * { box-sizing: border-box; }
        body { width: 72mm; margin: 0 auto; color: #000; background: #fff; font-family: Arial, sans-serif; font-size: 11px; line-height: 1.3; }
        .center { text-align: center; }
        .logo { display: block; width: 38mm; height: auto; margin: 0 auto 2mm; }
        .tracking { margin: 2mm 0; font-size: 18px; font-weight: 700; letter-spacing: .5px; }
        .divider { margin: 2mm 0; border-top: 1px dashed #000; }
        .row { margin: 1mm 0; overflow-wrap: anywhere; }
        .label { font-weight: 700; }
        .qr { display: block; width: 30mm; height: 30mm; margin: 2mm auto; object-fit: contain; }
        .muted { font-size: 9px; }
    </style>
</head>
<body>
    <header class="center">
        @if (! empty($payload['logo_data_uri']))
            <img class="logo" src="{{ $payload['logo_data_uri'] }}" alt="Tik Shop">
        @else
            <div class="tracking">Tik Shop</div>
        @endif
        <strong>{{ $payload['branch_name'] ?? 'Tik Shop' }}</strong>
        <div class="muted">{{ $payload['branch_address'] ?? config('tickets.fallback_address') }}</div>
        <div class="tracking">{{ $payload['tracking_code'] ?? 'Sin tracking' }}</div>
        <div>Ubicación: <strong>{{ $payload['storage_code'] ?? '—' }}</strong></div>
    </header>

    <div class="divider"></div>
    <div class="row"><span class="label">Categoría:</span> {{ $payload['category_name'] ?? '—' }}</div>
    <div class="row"><span class="label">Remitente:</span> {{ $payload['sender_name'] ?? '—' }}</div>
    <div class="row"><span class="label">Destinatario:</span> {{ $payload['recipient_name'] ?? '—' }}</div>
    <div class="row"><span class="label">Teléfono:</span> {{ $payload['recipient_phone'] ?? '—' }}</div>
    <div class="row"><span class="label">Descripción:</span> {{ $payload['description'] ?? '—' }}</div>
    <div class="row"><span class="label">Precio base:</span> Bs {{ $payload['storage_base_amount'] ?? $payload['storage_price'] ?? '—' }}</div>
    <div class="row"><span class="label">Tiempo almacenado:</span> {{ $payload['storage_days'] ?? '—' }} días</div>
    <div class="row"><span class="label">Recargo semanal:</span> Bs {{ $payload['storage_surcharge_amount'] ?? '0.00' }}</div>
    <div class="row"><span class="label">Total almacenaje:</span> Bs {{ $payload['storage_total_amount'] ?? $payload['storage_price'] ?? '—' }}</div>
    <div class="row"><span class="label">Recibido:</span> {{ $payload['received_at'] ?? '—' }}</div>

    @if (! empty($payload['qr_data_uri']))
        <div class="divider"></div>
        <img class="qr" src="{{ $payload['qr_data_uri'] }}" alt="QR de retiro">
    @endif

    <div class="divider"></div>
    <footer class="center muted">
        Simulación de impresión local · Papel {{ $printer['paper_width'] ?? 80 }} mm
    </footer>
</body>
</html>
