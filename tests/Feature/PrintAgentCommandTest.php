<?php

namespace Tests\Feature;

use App\Printing\PrintDriverInterface;
use App\Printing\PrintResult;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PrintAgentCommandTest extends TestCase
{
    public function test_local_agent_polls_renders_mock_evidence_and_reports_completion(): void
    {
        Storage::fake('local');
        config()->set('printing.driver', 'mock');
        config()->set('printing.mock.disk', 'local');
        config()->set('printing.agent.server_url', 'https://tikshop.test');
        config()->set('printing.agent.token', 'plain-agent-token');
        $job = $this->jobPayload('pending', 0);

        Http::fake(function (Request $request) use ($job) {
            if ($request->url() === 'https://tikshop.test/api/v1/print-agent/heartbeat') {
                return Http::response(['status' => 'ok']);
            }

            if ($request->url() === 'https://tikshop.test/api/v1/print-agent/jobs/next') {
                return Http::response(['data' => $job]);
            }

            if (str_ends_with($request->url(), '/processing')) {
                return Http::response(['data' => $this->jobPayload('processing', 1)]);
            }

            if (str_ends_with($request->url(), '/completed')) {
                return Http::response(['status' => 'completed']);
            }

            return Http::response([], 404);
        });

        $this->artisan('print-agent:work', ['--once' => true])
            ->expectsOutputToContain('Impresión simulada completada.')
            ->assertSuccessful();

        Storage::disk('local')->assertExists('prints/ticket-tik-260918-0001-01TESTPRINTJOB.html');
        Http::assertSent(fn (Request $request): bool => str_ends_with($request->url(), '/completed')
            && str_contains((string) $request['result_message'], 'prints/ticket-tik-260918-0001-01TESTPRINTJOB.html'));
        Http::assertSent(fn (Request $request): bool => $request->hasHeader('Authorization', 'Bearer plain-agent-token'));
    }

    public function test_local_agent_reports_driver_errors_as_failed(): void
    {
        config()->set('printing.agent.server_url', 'https://tikshop.test');
        config()->set('printing.agent.token', 'plain-agent-token');
        $job = $this->jobPayload('pending', 0);
        $this->app->bind(PrintDriverInterface::class, fn (): PrintDriverInterface => new class implements PrintDriverInterface
        {
            public function print(array $job): PrintResult
            {
                throw new \RuntimeException('Error controlado de prueba');
            }
        });

        Http::fake(function (Request $request) use ($job) {
            if (str_ends_with($request->url(), '/heartbeat')) {
                return Http::response(['status' => 'ok']);
            }
            if (str_ends_with($request->url(), '/jobs/next')) {
                return Http::response(['data' => $job]);
            }
            if (str_ends_with($request->url(), '/processing')) {
                return Http::response(['data' => $this->jobPayload('processing', 1)]);
            }
            if (str_ends_with($request->url(), '/failed')) {
                return Http::response(['status' => 'failed']);
            }

            return Http::response([], 404);
        });

        $this->artisan('print-agent:work', ['--once' => true])
            ->expectsOutputToContain('Impresión simulada fallida: Error controlado de prueba')
            ->assertSuccessful();

        Http::assertSent(fn (Request $request): bool => str_ends_with($request->url(), '/failed')
            && $request['error_message'] === 'Error controlado de prueba');
    }

    /** @return array<string, mixed> */
    private function jobPayload(string $status, int $attempts): array
    {
        return [
            'ulid' => '01TESTPRINTJOB',
            'type' => 'package_ticket',
            'status' => $status,
            'attempts' => $attempts,
            'payload' => [
                'tracking_code' => 'TIK-260918-0001',
                'branch_name' => 'Sucursal principal',
                'storage_code' => 'A1-01',
                'recipient_name' => 'Cliente prueba',
            ],
            'printer' => [
                'ulid' => '01TESTPRINTER',
                'name' => 'T-IM5003',
                'connection_type' => 'lan',
                'ip_address' => '192.168.1.50',
                'port' => 9100,
                'paper_width' => 80,
            ],
        ];
    }
}
