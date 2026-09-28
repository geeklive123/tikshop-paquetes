<?php

namespace Tests\Unit;

use App\Actions\Packages\ResolveTicketLogoAction;
use App\Printing\LanEscPosPrintDriver;
use App\Printing\MockPrintDriver;
use App\Printing\WindowsUsbPrintDriver;
use Illuminate\Support\Facades\Storage;
use LogicException;
use Tests\TestCase;

class MockPrintDriverTest extends TestCase
{
    public function test_mock_driver_writes_visual_ticket_evidence(): void
    {
        Storage::fake('local');
        config()->set('printing.mock.disk', 'local');
        config()->set('printing.mock.directory', 'prints');
        $logoDataUri = app(ResolveTicketLogoAction::class)->execute();

        $result = app(MockPrintDriver::class)->print([
            'ulid' => '01TESTJOB',
            'payload' => [
                'tracking_code' => 'TIK-260918-0001',
                'branch_name' => 'Sucursal principal',
                'storage_code' => 'A1-01',
                'recipient_name' => 'Cliente prueba',
                'logo_data_uri' => $logoDataUri,
            ],
            'printer' => ['paper_width' => 80],
        ]);

        $this->assertIsString($logoDataUri);
        $this->assertStringStartsWith('data:image/png;base64,', $logoDataUri);
        $this->assertSame('prints/ticket-tik-260918-0001-01TESTJOB.html', $result->evidencePath);
        Storage::disk('local')->assertExists($result->evidencePath);
        $html = Storage::disk('local')->get($result->evidencePath);
        $this->assertStringContainsString('TIK-260918-0001', $html);
        $this->assertStringContainsString('size: 80mm auto', $html);
        $this->assertStringContainsString('Cliente prueba', $html);
        $this->assertStringContainsString('Ayacucho y General Acha, al lado de Entel - Edificio Galindo, 2do piso', $html);
        $this->assertStringContainsString('<img class="logo" src="'.$logoDataUri.'"', $html);
        $this->assertStringContainsString('width: 38mm', $html);
    }

    public function test_mock_ticket_uses_text_fallback_when_logo_is_unavailable(): void
    {
        Storage::fake('local');
        config()->set('printing.mock.disk', 'local');

        $result = app(MockPrintDriver::class)->print([
            'ulid' => '01NOLOGO',
            'payload' => ['tracking_code' => 'TIK-SIN-LOGO', 'logo_data_uri' => null],
            'printer' => ['paper_width' => 80],
        ]);

        $html = Storage::disk('local')->get($result->evidencePath);
        $this->assertStringContainsString('<div class="tracking">Tik Shop</div>', $html);
        $this->assertStringNotContainsString('<img class="logo"', $html);
    }

    public function test_real_drivers_are_safe_stubs_in_simulation_phase(): void
    {
        foreach ([new LanEscPosPrintDriver, new WindowsUsbPrintDriver] as $driver) {
            try {
                $driver->print([]);
                $this->fail('El stub no rechazó la impresión real.');
            } catch (LogicException $exception) {
                $this->assertStringContainsString('modo simulación', $exception->getMessage());
            }
        }
    }
}
