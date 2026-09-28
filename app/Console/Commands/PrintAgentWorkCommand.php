<?php

namespace App\Console\Commands;

use App\Printing\PrintDriverInterface;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use Throwable;

#[Signature('print-agent:work {--once : Consultar una vez y finalizar}')]
#[Description('Consulta y procesa trabajos del agente local de impresión')]
class PrintAgentWorkCommand extends Command
{
    public function handle(PrintDriverInterface $driver): int
    {
        $serverUrl = rtrim((string) config('printing.agent.server_url'), '/');
        $token = config('printing.agent.token');

        if ($serverUrl === '' || ! is_string($token) || $token === '') {
            $this->error('Configura PRINT_AGENT_SERVER_URL y PRINT_AGENT_TOKEN.');

            return self::FAILURE;
        }

        $client = Http::baseUrl($serverUrl.'/api/v1/print-agent')
            ->withToken($token)
            ->acceptJson()
            ->connectTimeout(3)
            ->timeout(10);

        do {
            try {
                $client->post('/heartbeat')->throw();
                $response = $client->get('/jobs/next');
                $response->throw();

                if ($response->status() === 204) {
                    $this->line('No hay trabajos pendientes.');
                } else {
                    $this->processJob($client, $driver, $response->json('data'));
                }
            } catch (Throwable $exception) {
                $this->error('No se pudo consultar Laravel: '.$exception->getMessage());

                if ($this->option('once')) {
                    return self::FAILURE;
                }
            }

            if (! $this->option('once')) {
                sleep(max(1, (int) config('printing.agent.poll_seconds', 5)));
            }
        } while (! $this->option('once'));

        return self::SUCCESS;
    }

    /** @param array<string, mixed>|null $job */
    private function processJob(PendingRequest $client, PrintDriverInterface $driver, ?array $job): void
    {
        if (! is_array($job) || ! is_string($job['ulid'] ?? null)) {
            throw new \RuntimeException('Laravel devolvió un trabajo inválido.');
        }

        $ulid = $job['ulid'];
        $claimedJob = $client->post("/jobs/{$ulid}/processing")->throw()->json('data');

        if (! is_array($claimedJob)) {
            throw new \RuntimeException('Laravel no devolvió el trabajo tomado.');
        }

        try {
            $result = $driver->print($claimedJob);
            $message = $result->evidencePath === null
                ? $result->message
                : $result->message.' Evidencia: '.$result->evidencePath;

            $client->post("/jobs/{$ulid}/completed", ['result_message' => $message])->throw();
            $this->info($message);
        } catch (Throwable $exception) {
            $client->post("/jobs/{$ulid}/failed", [
                'error_message' => mb_substr($exception->getMessage(), 0, 2000),
            ])->throw();
            $this->error('Impresión simulada fallida: '.$exception->getMessage());
        }
    }
}
