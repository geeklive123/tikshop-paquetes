<?php

namespace Database\Factories;

use App\Enums\SellerDocumentType;
use App\Models\Company;
use App\Models\Seller;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Seller>
 */
class SellerFactory extends Factory
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
            'name' => fake()->name(),
            'business_name' => fake()->optional()->company(),
            'phone' => fake()->numerify('7#######'),
            'document_type' => fake()->optional()->randomElement(SellerDocumentType::cases()),
            'document_number' => fake()->optional()->numerify('#######'),
            'address' => fake()->optional()->address(),
            'notes' => fake()->optional()->sentence(),
            'active' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (): array => ['active' => false]);
    }
}
