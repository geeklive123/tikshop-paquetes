<?php

namespace Tests\Feature;

use App\Enums\PrinterConnectionType;
use App\Enums\UserRole;
use App\Models\Branch;
use App\Models\Company;
use App\Models\Printer;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class PrinterManagementTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_owner_can_create_lan_printer_for_their_company(): void
    {
        $company = Company::factory()->create();
        $branch = Branch::factory()->for($company)->create();
        $owner = User::factory()->for($company)->withRole(UserRole::Owner)->create();

        $response = $this->actingAs($owner)->post(route('printers.store'), $this->validPayload($branch));

        $printer = Printer::query()->sole();
        $response->assertRedirect(route('printers.edit', $printer));
        $this->assertSame($company->id, $printer->company_id);
        $this->assertSame($branch->id, $printer->branch_id);
        $this->assertSame(PrinterConnectionType::Lan, $printer->connection_type);
        $this->assertSame('192.168.1.50', $printer->ip_address);
        $this->assertSame(9100, $printer->port);
        $this->assertSame(2, $printer->copies);
    }

    public function test_lan_printer_requires_valid_ip_and_port(): void
    {
        $company = Company::factory()->create();
        $branch = Branch::factory()->for($company)->create();
        $owner = User::factory()->for($company)->withRole(UserRole::Owner)->create();

        $response = $this->actingAs($owner)->post(route('printers.store'), [
            ...$this->validPayload($branch),
            'ip_address' => 'not-an-ip',
            'port' => '70000',
        ]);

        $response->assertInvalid(['ip_address', 'port']);
        $this->assertDatabaseCount('printers', 0);
    }

    public function test_printer_rejects_more_than_two_copies(): void
    {
        $company = Company::factory()->create();
        $branch = Branch::factory()->for($company)->create();
        $owner = User::factory()->for($company)->withRole(UserRole::Owner)->create();

        $response = $this->actingAs($owner)->post(route('printers.store'), [
            ...$this->validPayload($branch),
            'copies' => '3',
        ]);

        $response->assertInvalid(['copies']);
        $this->assertDatabaseCount('printers', 0);
    }

    public function test_branch_from_another_company_is_rejected(): void
    {
        $owner = User::factory()->withRole(UserRole::Owner)->create();
        $otherBranch = Branch::factory()->create();

        $response = $this->actingAs($owner)->post(route('printers.store'), $this->validPayload($otherBranch));

        $response->assertInvalid(['branch_id']);
        $this->assertDatabaseCount('printers', 0);
    }

    public function test_only_one_printer_is_default_per_branch(): void
    {
        $company = Company::factory()->create();
        $branch = Branch::factory()->for($company)->create();
        $owner = User::factory()->for($company)->withRole(UserRole::Owner)->create();
        $originalDefault = Printer::factory()->forBranch($branch)->default()->create();

        $this->actingAs($owner)->post(route('printers.store'), [
            ...$this->validPayload($branch),
            'name' => 'T-IM5003 secundaria',
        ])->assertRedirect();

        $newDefault = Printer::query()->where('name', 'T-IM5003 secundaria')->sole();
        $this->assertFalse($originalDefault->fresh()->is_default);
        $this->assertTrue($newDefault->is_default);
        $this->assertSame(1, Printer::query()->whereBelongsTo($branch)->where('is_default', true)->count());
    }

    public function test_inactive_printer_cannot_be_default(): void
    {
        $company = Company::factory()->create();
        $branch = Branch::factory()->for($company)->create();
        $owner = User::factory()->for($company)->withRole(UserRole::Owner)->create();

        $response = $this->actingAs($owner)->post(route('printers.store'), [
            ...$this->validPayload($branch),
            'active' => '0',
            'is_default' => '1',
        ]);

        $response->assertInvalid(['is_default']);
        $this->assertDatabaseCount('printers', 0);
    }

    public function test_operator_can_view_printers_but_cannot_edit_them(): void
    {
        $company = Company::factory()->create();
        $branch = Branch::factory()->for($company)->create();
        $operator = User::factory()->for($company)->withRole(UserRole::Operator)->create();
        $printer = Printer::factory()->forBranch($branch)->create(['name' => 'Caja principal']);

        $this->actingAs($operator)->get(route('printers.index'))->assertSee('Caja principal');
        $this->actingAs($operator)->get(route('printers.edit', $printer))->assertForbidden();
        $this->actingAs($operator)->put(route('printers.update', $printer), $this->validPayload($branch))->assertForbidden();
    }

    public function test_admin_can_update_printer(): void
    {
        $company = Company::factory()->create();
        $branch = Branch::factory()->for($company)->create();
        $admin = User::factory()->for($company)->withRole(UserRole::Admin)->create();
        $printer = Printer::factory()->forBranch($branch)->create();

        $response = $this->actingAs($admin)->put(route('printers.update', $printer), [
            ...$this->validPayload($branch),
            'name' => 'T-IM5003 actualizada',
        ]);

        $response->assertRedirect(route('printers.edit', $printer));
        $this->assertSame('T-IM5003 actualizada', $printer->fresh()->name);
    }

    public function test_printers_are_isolated_between_companies(): void
    {
        $owner = User::factory()->withRole(UserRole::Owner)->create();
        $ownBranch = Branch::factory()->for($owner->company)->create();
        Printer::factory()->forBranch($ownBranch)->create(['name' => 'Impresora propia']);
        $otherPrinter = Printer::factory()->create(['name' => 'Impresora ajena']);

        $this->actingAs($owner)
            ->get(route('printers.index'))
            ->assertSee('Impresora propia')
            ->assertDontSee('Impresora ajena');
        $this->actingAs($owner)->get(route('printers.edit', $otherPrinter))->assertNotFound();
    }

    public function test_deactivating_default_printer_removes_default_status(): void
    {
        $company = Company::factory()->create();
        $branch = Branch::factory()->for($company)->create();
        $owner = User::factory()->for($company)->withRole(UserRole::Owner)->create();
        $printer = Printer::factory()->forBranch($branch)->default()->create();

        $response = $this->actingAs($owner)->patch(route('printers.status.update', $printer));

        $response->assertRedirect(route('printers.index'));
        $this->assertFalse($printer->fresh()->active);
        $this->assertFalse($printer->fresh()->is_default);
    }

    public function test_private_lan_connection_test_requires_local_print_agent(): void
    {
        $company = Company::factory()->create();
        $branch = Branch::factory()->for($company)->create();
        $owner = User::factory()->for($company)->withRole(UserRole::Owner)->create();
        $printer = Printer::factory()->forBranch($branch)->create(['ip_address' => '192.168.1.50']);

        $response = $this->actingAs($owner)->post(route('printers.test-connection', $printer));

        $response->assertRedirect();
        $response->assertSessionHas('status', 'La prueba real de conectividad requiere el agente local de impresión.');
    }

    public function test_connection_test_rejects_invalid_stored_configuration_without_opening_a_connection(): void
    {
        $company = Company::factory()->create();
        $branch = Branch::factory()->for($company)->create();
        $owner = User::factory()->for($company)->withRole(UserRole::Owner)->create();
        $printer = Printer::factory()->forBranch($branch)->create(['port' => null]);

        $response = $this->actingAs($owner)->post(route('printers.test-connection', $printer));

        $response->assertRedirect();
        $response->assertSessionHas('status', 'La configuración de la impresora no es válida. Revisa la conexión, el puerto y el ancho de papel.');
    }

    /** @return array<string, string> */
    private function validPayload(Branch $branch): array
    {
        return [
            'name' => 'T-IM5003 caja',
            'branch_id' => (string) $branch->id,
            'connection_type' => 'lan',
            'ip_address' => '192.168.1.50',
            'port' => '9100',
            'paper_width' => '80',
            'copies' => '2',
            'is_default' => '1',
            'active' => '1',
            'notes' => 'Impresora térmica de recepción.',
        ];
    }
}
