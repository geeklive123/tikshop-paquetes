<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\Seller;
use App\Models\SellerCommissionSettlement;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SellerCommissionSettlement>
 */
class SellerCommissionSettlementFactory extends Factory
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
            'seller_id' => fn (array $attributes): int => Seller::factory()->create([
                'company_id' => $attributes['company_id'],
            ])->id,
            'date_from' => now()->startOfMonth()->toDateString(),
            'date_to' => now()->endOfMonth()->toDateString(),
            'delivered_packages_count' => 1,
            'commission_total' => '0.50',
            'paid_at' => now(),
            'paid_by' => fn (array $attributes): int => User::factory()->create([
                'company_id' => $attributes['company_id'],
            ])->id,
            'notes' => null,
        ];
    }
}
