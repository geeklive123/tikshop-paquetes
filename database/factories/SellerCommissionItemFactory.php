<?php

namespace Database\Factories;

use App\Enums\PackageStatus;
use App\Models\Package;
use App\Models\SellerCommissionItem;
use App\Models\SellerCommissionSettlement;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SellerCommissionItem>
 */
class SellerCommissionItemFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'settlement_id' => SellerCommissionSettlement::factory(),
            'package_id' => function (array $attributes): int {
                $settlement = SellerCommissionSettlement::query()->findOrFail($attributes['settlement_id']);

                return Package::factory()
                    ->for($settlement->company)
                    ->for($settlement->seller)
                    ->withStatus(PackageStatus::Delivered)
                    ->create(['delivered_at' => now()])
                    ->id;
            },
            'category_name_snapshot' => 'Pequeño',
            'commission_rate' => '0.50',
            'commission_amount' => '0.50',
        ];
    }
}
