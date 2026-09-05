<?php

namespace Database\Factories;

use App\Enums\PackageEventType;
use App\Models\Package;
use App\Models\PackageEvent;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PackageEvent>
 */
class PackageEventFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'package_id' => Package::factory(),
            'user_id' => null,
            'event' => PackageEventType::PackageCreated,
            'metadata' => null,
        ];
    }
}
