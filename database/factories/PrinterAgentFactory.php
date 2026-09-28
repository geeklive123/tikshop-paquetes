<?php

namespace Database\Factories;

use App\Models\Branch;
use App\Models\Company;
use App\Models\PrinterAgent;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PrinterAgent>
 */
class PrinterAgentFactory extends Factory
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
            'name' => 'Agente '.fake()->unique()->word(),
            'token_hash' => hash('sha256', fake()->unique()->uuid()),
            'active' => true,
            'last_seen_at' => null,
        ];
    }

    public function forBranch(Branch $branch): static
    {
        return $this->state(fn (): array => [
            'company_id' => $branch->company_id,
            'branch_id' => $branch->id,
        ]);
    }

    public function online(): static
    {
        return $this->state(fn (): array => ['last_seen_at' => now()]);
    }
}
