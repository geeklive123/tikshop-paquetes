<?php

namespace App\Printing;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class MockPrintDriver implements PrintDriverInterface
{
    public function print(array $job): PrintResult
    {
        $payload = is_array($job['payload'] ?? null) ? $job['payload'] : [];
        $trackingCode = Str::slug((string) ($payload['tracking_code'] ?? 'ticket'));
        $jobUlid = (string) ($job['ulid'] ?? Str::ulid());
        $directory = trim((string) config('printing.mock.directory', 'prints'), '/');
        $path = $directory.'/ticket-'.$trackingCode.'-'.$jobUlid.'.html';
        $html = view('prints.mock-ticket', [
            'payload' => $payload,
            'printer' => is_array($job['printer'] ?? null) ? $job['printer'] : [],
        ])->render();

        $written = Storage::disk((string) config('printing.mock.disk', 'local'))->put($path, $html);

        if (! $written) {
            throw new \RuntimeException('No se pudo guardar la evidencia de impresión simulada.');
        }

        return new PrintResult('Impresión simulada completada.', $path);
    }
}
