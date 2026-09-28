<?php

namespace Database\Factories;

use App\Enums\PrinterConnectionType;
use App\Models\Branch;
use App\Models\Company;
use App\Models\Printer;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Printer>
 */
class PrinterFactory extends Factory
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
                return Branch::factory()->create([
                    'company_id' => $attributes['company_id'],
                ])->id;
            },
            'name' => 'Impresora '.fake()->unique()->word(),
            'connection_type' => PrinterConnectionType::Lan,
            'ip_address' => fake()->localIpv4(),
            'port' => 9100,
            'paper_width' => 80,
            'is_default' => false,
            'active' => true,
            'notes' => null,
        ];
    }

    public function forBranch(Branch $branch): static
    {
        return $this->state(fn (): array => [
            'company_id' => $branch->company_id,
            'branch_id' => $branch->id,
        ]);
    }

    public function usb(): static
    {
        return $this->state(fn (): array => [
            'connection_type' => PrinterConnectionType::Usb,
            'ip_address' => null,
            'port' => null,
        ]);
    }

    public function default(): static
    {
        return $this->state(fn (): array => [
            'is_default' => true,
            'active' => true,
        ]);
    }
}
