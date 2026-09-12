<?php

namespace Database\Factories;

use App\Models\Package;
use App\Models\PackagePickupToken;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<PackagePickupToken>
 */
class PackagePickupTokenFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $rawToken = Str::random(64);

        return [
            'package_id' => Package::factory(),
            'token_hash' => hash('sha256', $rawToken),
            'token_encrypted' => $rawToken,
            'expires_at' => null,
            'used_at' => null,
            'revoked_at' => null,
        ];
    }

    public function expired(): static
    {
        return $this->state(fn (): array => ['expires_at' => now()->subMinute()]);
    }

    public function used(): static
    {
        return $this->state(fn (): array => ['used_at' => now()->subMinute()]);
    }

    public function revoked(): static
    {
        return $this->state(fn (): array => ['revoked_at' => now()->subMinute()]);
    }
}
