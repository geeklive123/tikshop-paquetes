<?php

namespace Database\Factories;

use App\Enums\PackageStatus;
use App\Models\Branch;
use App\Models\Company;
use App\Models\Package;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Package>
 */
class PackageFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $receivedAt = fake()->dateTimeBetween('-30 days');

        return [
            'company_id' => Company::factory(),
            'branch_id' => fn (array $attributes): int => Branch::factory()->create([
                'company_id' => $attributes['company_id'],
            ])->id,
            'tracking_code' => sprintf('TIK-%s-%04d', $receivedAt->format('ymd'), fake()->unique()->numberBetween(1, 9999)),
            'sender_name' => fake()->name(),
            'sender_phone' => fake()->phoneNumber(),
            'recipient_name' => fake()->name(),
            'recipient_phone' => fake()->phoneNumber(),
            'description' => fake()->optional()->sentence(),
            'notes' => fake()->optional()->sentence(),
            'status' => PackageStatus::Received,
            'received_at' => $receivedAt,
            'ready_at' => null,
            'delivered_at' => null,
            'cancelled_at' => null,
            'received_by' => fn (array $attributes): int => User::factory()->create([
                'company_id' => $attributes['company_id'],
            ])->id,
            'delivered_by' => null,
        ];
    }

    public function forBranch(Branch $branch): static
    {
        return $this->state(fn (): array => [
            'company_id' => $branch->company_id,
            'branch_id' => $branch->id,
            'received_by' => User::factory()->create(['company_id' => $branch->company_id])->id,
        ]);
    }

    public function withStatus(PackageStatus $status): static
    {
        return $this->state(fn (): array => ['status' => $status]);
    }
}
