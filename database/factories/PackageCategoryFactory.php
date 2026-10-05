<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\PackageCategory;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PackageCategory>
 */
class PackageCategoryFactory extends Factory
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
            'name' => fake()->unique()->word(),
            'code_prefix' => strtoupper(fake()->unique()->lexify('??')),
            'code_start' => 1,
            'code_end' => 10,
            'price' => fake()->randomFloat(2, 1, 20),
            'commission_rate' => null,
            'weekly_storage_increment' => '0.00',
            'color' => '#E5252A',
            'active' => true,
        ];
    }
}
