<?php

namespace Database\Factories;

use App\Enums\PrintJobStatus;
use App\Enums\PrintJobType;
use App\Models\Branch;
use App\Models\Company;
use App\Models\Package;
use App\Models\Printer;
use App\Models\PrintJob;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PrintJob>
 */
class PrintJobFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'branch_id' => function (array $attributes): int {
                return Branch::factory()->create(['company_id' => $attributes['company_id']])->id;
            },
            'printer_id' => function (array $attributes): int {
                return Printer::factory()->create([
                    'company_id' => $attributes['company_id'],
                    'branch_id' => $attributes['branch_id'],
                ])->id;
            },
            'package_id' => null,
            'printer_agent_id' => null,
            'type' => PrintJobType::PackageTicket,
            'status' => PrintJobStatus::Pending,
            'attempts' => 0,
            'payload' => $this->ticketPayload(),
            'error_message' => null,
            'requested_by' => function (array $attributes): int {
                return User::factory()->create(['company_id' => $attributes['company_id']])->id;
            },
            'claimed_at' => null,
            'completed_at' => null,
            'failed_at' => null,
        ];
    }

    public function forBranch(Branch $branch): static
    {
        return $this->state(fn (): array => [
            'company_id' => $branch->company_id,
            'branch_id' => $branch->id,
            'printer_id' => Printer::factory()->forBranch($branch),
            'requested_by' => User::factory()->create(['company_id' => $branch->company_id])->id,
        ]);
    }

    public function forPackage(Package $package, Printer $printer, User $user): static
    {
        return $this->state(fn (): array => [
            'company_id' => $package->company_id,
            'branch_id' => $package->branch_id,
            'printer_id' => $printer->id,
            'package_id' => $package->id,
            'requested_by' => $user->id,
            'payload' => [
                ...$this->ticketPayload(),
                'tracking_code' => $package->tracking_code,
            ],
        ]);
    }

    public function failed(): static
    {
        return $this->state(fn (): array => [
            'status' => PrintJobStatus::Failed,
            'attempts' => 1,
            'error_message' => 'Error simulado',
            'failed_at' => now(),
        ]);
    }

    /** @return array<string, string|null> */
    private function ticketPayload(): array
    {
        return [
            'tracking_code' => 'TIK-TEST-001',
            'branch_name' => 'Sucursal principal',
            'storage_code' => 'A1-01',
            'category_name' => 'Mediano',
            'sender_name' => 'Remitente',
            'recipient_name' => 'Destinatario',
            'recipient_phone' => '70000000',
            'description' => 'Paquete de prueba',
            'storage_price' => '5.00',
            'received_at' => '2026-09-18T10:00:00-04:00',
            'qr_data_uri' => null,
            'logo_data_uri' => null,
        ];
    }
}
