<?php

namespace Tests\Feature;

use App\Enums\PackageStatus;
use App\Enums\UserRole;
use App\Models\Company;
use App\Models\Package;
use App\Models\PackageCategory;
use App\Models\Seller;
use App\Models\SellerCommissionItem;
use App\Models\SellerCommissionSettlement;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class SellerCommissionSettlementTest extends TestCase
{
    use LazilyRefreshDatabase;

    /** @return array<string, array{string, string}> */
    public static function commissionRates(): array
    {
        return [
            'small' => ['Pequeño', '0.50'],
            'medium' => ['Mediano', '0.70'],
            'large' => ['Grande', '1.00'],
        ];
    }

    #[DataProvider('commissionRates')]
    public function test_calculation_uses_the_category_commission_rate(string $categoryName, string $rate): void
    {
        [$owner, $seller] = $this->ownerAndSeller();
        $category = PackageCategory::factory()->for($owner->company)->create([
            'name' => $categoryName,
            'commission_rate' => $rate,
        ]);
        $this->createPackage($seller, $category, PackageStatus::Delivered, '2026-10-05 12:00:00');

        $response = $this->actingAs($owner)->get(route('sellers.commissions.calculate', [
            'seller' => $seller, 'date_from' => '2026-10-01', 'date_to' => '2026-10-07',
        ]));

        $response->assertViewHas('calculation', fn (array $calculation): bool => $calculation['commissionTotal'] === $rate
            && $calculation['payablePackages']->count() === 1
            && $calculation['breakdown'][0]['rate'] === $rate);
    }

    public function test_only_delivered_packages_generate_commission(): void
    {
        [$owner, $seller] = $this->ownerAndSeller();
        $category = PackageCategory::factory()->for($owner->company)->create(['commission_rate' => '0.50']);
        $this->createPackage($seller, $category, PackageStatus::Received, null);
        $this->createPackage($seller, $category, PackageStatus::ReadyForPickup, null);
        $this->createPackage($seller, $category, PackageStatus::Cancelled, null);
        $this->createPackage($seller, $category, PackageStatus::Delivered, '2026-10-05 12:00:00');

        $response = $this->actingAs($owner)->get(route('sellers.commissions.calculate', [
            'seller' => $seller, 'date_from' => '2026-10-01', 'date_to' => '2026-10-07',
        ]));

        $response->assertViewHas('calculation', fn (array $calculation): bool => $calculation['deliveredPackages'] === 1
            && $calculation['unpaidDeliveredPackages'] === 1
            && $calculation['pendingPackages'] === 2
            && $calculation['commissionTotal'] === '0.50');
    }

    public function test_range_filter_uses_delivered_at_instead_of_received_at(): void
    {
        [$owner, $seller] = $this->ownerAndSeller();
        $category = PackageCategory::factory()->for($owner->company)->create(['commission_rate' => '0.70']);
        $this->createPackage($seller, $category, PackageStatus::Delivered, '2026-10-05 10:00:00', '2026-09-01 10:00:00');
        $this->createPackage($seller, $category, PackageStatus::Delivered, '2026-09-30 23:59:59', '2026-10-05 10:00:00');

        $response = $this->actingAs($owner)->get(route('sellers.commissions.calculate', [
            'seller' => $seller, 'date_from' => '2026-10-01', 'date_to' => '2026-10-07',
        ]));

        $response->assertViewHas('calculation', fn (array $calculation): bool => $calculation['deliveredPackages'] === 1
            && $calculation['commissionTotal'] === '0.70');
    }

    public function test_category_without_rate_is_not_invented_or_paid(): void
    {
        [$owner, $seller] = $this->ownerAndSeller();
        $category = PackageCategory::factory()->for($owner->company)->create([
            'name' => 'Muy Grande', 'commission_rate' => null,
        ]);
        $this->createPackage($seller, $category, PackageStatus::Delivered, '2026-10-05 12:00:00');

        $response = $this->actingAs($owner)->get(route('sellers.commissions.calculate', [
            'seller' => $seller, 'date_from' => '2026-10-01', 'date_to' => '2026-10-07',
        ]));

        $response->assertViewHas('calculation', fn (array $calculation): bool => $calculation['unpaidDeliveredPackages'] === 1
            && $calculation['missingRatePackages'] === 1
            && $calculation['payablePackages']->isEmpty()
            && $calculation['commissionTotal'] === '0.00');
    }

    public function test_overlapping_ranges_do_not_pay_the_same_package_twice(): void
    {
        [$owner, $seller] = $this->ownerAndSeller();
        $category = PackageCategory::factory()->for($owner->company)->create(['commission_rate' => '0.50']);
        $firstPackage = $this->createPackage($seller, $category, PackageStatus::Delivered, '2026-10-03 12:00:00');
        $secondPackage = $this->createPackage($seller, $category, PackageStatus::Delivered, '2026-10-10 12:00:00');

        $this->actingAs($owner)->post(route('sellers.commission-settlements.store', $seller), [
            'date_from' => '2026-10-01', 'date_to' => '2026-10-07',
        ])->assertRedirect();
        $this->actingAs($owner)->post(route('sellers.commission-settlements.store', $seller), [
            'date_from' => '2026-10-01', 'date_to' => '2026-10-15',
        ])->assertRedirect();

        $this->assertSame(2, SellerCommissionSettlement::query()->count());
        $this->assertSame(2, SellerCommissionItem::query()->count());
        $this->assertSame(1, SellerCommissionItem::query()->where('package_id', $firstPackage->id)->count());
        $this->assertSame(1, SellerCommissionItem::query()->where('package_id', $secondPackage->id)->count());
    }

    public function test_paid_package_no_longer_appears_as_payable(): void
    {
        [$owner, $seller] = $this->ownerAndSeller();
        $category = PackageCategory::factory()->for($owner->company)->create(['commission_rate' => '1.00']);
        $this->createPackage($seller, $category, PackageStatus::Delivered, '2026-10-05 12:00:00');
        $payload = ['date_from' => '2026-10-01', 'date_to' => '2026-10-07'];

        $this->actingAs($owner)->post(route('sellers.commission-settlements.store', $seller), $payload);
        $response = $this->actingAs($owner)->get(route('sellers.commissions.calculate', ['seller' => $seller, ...$payload]));

        $response->assertViewHas('calculation', fn (array $calculation): bool => $calculation['deliveredPackages'] === 1
            && $calculation['unpaidDeliveredPackages'] === 0
            && $calculation['commissionTotal'] === '0.00');
    }

    public function test_future_rate_change_does_not_change_historical_snapshot(): void
    {
        [$owner, $seller] = $this->ownerAndSeller();
        $category = PackageCategory::factory()->for($owner->company)->create([
            'name' => 'Pequeño', 'commission_rate' => '0.50',
        ]);
        $firstPackage = $this->createPackage($seller, $category, PackageStatus::Delivered, '2026-10-05 12:00:00');
        $this->actingAs($owner)->post(route('sellers.commission-settlements.store', $seller), [
            'date_from' => '2026-10-05', 'date_to' => '2026-10-05',
        ]);

        $category->update(['commission_rate' => '1.25']);
        $secondPackage = $this->createPackage($seller, $category, PackageStatus::Delivered, '2026-10-06 12:00:00');
        $this->actingAs($owner)->post(route('sellers.commission-settlements.store', $seller), [
            'date_from' => '2026-10-06', 'date_to' => '2026-10-06',
        ]);

        $this->assertSame('0.50', SellerCommissionItem::query()->where('package_id', $firstPackage->id)->value('commission_rate'));
        $this->assertSame('1.25', SellerCommissionItem::query()->where('package_id', $secondPackage->id)->value('commission_rate'));
    }

    public function test_seller_from_another_company_is_rejected(): void
    {
        $owner = User::factory()->withRole(UserRole::Owner)->create();
        $otherSeller = Seller::factory()->create();

        $this->actingAs($owner)->get(route('sellers.commissions.index', $otherSeller))->assertNotFound();
        $this->actingAs($owner)->post(route('sellers.commission-settlements.store', $otherSeller), [
            'date_from' => '2026-10-01', 'date_to' => '2026-10-07',
        ])->assertForbidden();
        $this->assertSame(0, SellerCommissionSettlement::query()->count());
    }

    public function test_operator_can_read_history_but_cannot_confirm_payment(): void
    {
        $company = Company::factory()->create();
        $operator = User::factory()->for($company)->withRole(UserRole::Operator)->create();
        $seller = Seller::factory()->for($company)->create();

        $this->actingAs($operator)->get(route('sellers.commissions.index', $seller))
            ->assertSee('Historial de liquidaciones pagadas')
            ->assertDontSee('Calcular comisión');
        $this->actingAs($operator)->post(route('sellers.commission-settlements.store', $seller), [
            'date_from' => '2026-10-01', 'date_to' => '2026-10-07',
        ])->assertForbidden();
    }

    /** @return array<string, array{UserRole}> */
    public static function settlementRoles(): array
    {
        return ['owner' => [UserRole::Owner], 'admin' => [UserRole::Admin]];
    }

    #[DataProvider('settlementRoles')]
    public function test_owner_and_admin_can_confirm_payment(UserRole $role): void
    {
        $company = Company::factory()->create();
        $user = User::factory()->for($company)->withRole($role)->create();
        $seller = Seller::factory()->for($company)->create();
        $category = PackageCategory::factory()->for($company)->create(['commission_rate' => '0.70']);
        $this->createPackage($seller, $category, PackageStatus::Delivered, '2026-10-05 12:00:00');

        $response = $this->actingAs($user)->post(route('sellers.commission-settlements.store', $seller), [
            'date_from' => '2026-10-01', 'date_to' => '2026-10-07', 'notes' => 'Pago semanal',
        ]);

        $settlement = SellerCommissionSettlement::query()->sole();
        $response->assertRedirect(route('sellers.commission-settlements.show', [$seller, $settlement]));
        $this->assertSame($user->id, $settlement->paid_by);
        $this->assertSame('0.70', $settlement->commission_total);
        $this->assertNotNull($settlement->paid_at);
        $this->assertSame('Pago semanal', $settlement->notes);
    }

    public function test_double_submit_does_not_create_a_second_payment(): void
    {
        [$owner, $seller] = $this->ownerAndSeller();
        $category = PackageCategory::factory()->for($owner->company)->create(['commission_rate' => '0.50']);
        $this->createPackage($seller, $category, PackageStatus::Delivered, '2026-10-05 12:00:00');
        $payload = ['date_from' => '2026-10-01', 'date_to' => '2026-10-07'];

        $this->actingAs($owner)->post(route('sellers.commission-settlements.store', $seller), $payload)->assertRedirect();
        $this->actingAs($owner)->post(route('sellers.commission-settlements.store', $seller), $payload)
            ->assertSessionHasErrors('date_from');

        $this->assertSame(1, SellerCommissionSettlement::query()->count());
        $this->assertSame(1, SellerCommissionItem::query()->count());
    }

    public function test_database_unique_constraint_prevents_duplicate_package_items(): void
    {
        [$owner, $seller] = $this->ownerAndSeller();
        $category = PackageCategory::factory()->for($owner->company)->create(['commission_rate' => '0.50']);
        $package = $this->createPackage($seller, $category, PackageStatus::Delivered, '2026-10-05 12:00:00');
        $firstSettlement = SellerCommissionSettlement::factory()->for($owner->company)->for($seller)->create(['paid_by' => $owner->id]);
        $secondSettlement = SellerCommissionSettlement::factory()->for($owner->company)->for($seller)->create(['paid_by' => $owner->id]);
        SellerCommissionItem::factory()->for($firstSettlement, 'settlement')->for($package)->create();

        $this->expectException(UniqueConstraintViolationException::class);
        SellerCommissionItem::factory()->for($secondSettlement, 'settlement')->for($package)->create();
    }

    public function test_settlement_total_is_the_exact_sum_of_items(): void
    {
        [$owner, $seller] = $this->ownerAndSeller();
        foreach ([['Pequeño', '0.50'], ['Mediano', '0.70'], ['Grande', '1.00']] as [$name, $rate]) {
            $category = PackageCategory::factory()->for($owner->company)->create(['name' => $name, 'commission_rate' => $rate]);
            $this->createPackage($seller, $category, PackageStatus::Delivered, '2026-10-05 12:00:00');
        }

        $this->actingAs($owner)->post(route('sellers.commission-settlements.store', $seller), [
            'date_from' => '2026-10-01', 'date_to' => '2026-10-07',
        ]);

        $settlement = SellerCommissionSettlement::query()->sole();
        $this->assertSame('2.20', $settlement->commission_total);
        $this->assertSame('2.20', number_format((float) $settlement->items()->sum('commission_amount'), 2, '.', ''));
        $this->assertSame(3, $settlement->delivered_packages_count);
    }

    public function test_date_boundaries_use_america_la_paz(): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-10-01 00:30:00', 'America/La_Paz'));
        [$owner, $seller] = $this->ownerAndSeller();
        $category = PackageCategory::factory()->for($owner->company)->create(['commission_rate' => '0.50']);
        $this->createPackage($seller, $category, PackageStatus::Delivered, '2026-09-30 23:59:59');
        $this->createPackage($seller, $category, PackageStatus::Delivered, '2026-10-01 00:00:00');

        $response = $this->actingAs($owner)->get(route('sellers.commissions.calculate', [
            'seller' => $seller, 'date_from' => '2026-09-30', 'date_to' => '2026-09-30',
        ]));

        $this->assertSame('America/La_Paz', config('app.timezone'));
        $response->assertViewHas('calculation', fn (array $calculation): bool => $calculation['deliveredPackages'] === 1
            && $calculation['dateFrom'] === '2026-09-30'
            && $calculation['dateTo'] === '2026-09-30');
    }

    public function test_history_and_paid_commission_report_show_the_payment(): void
    {
        $this->travelTo('2026-10-08 09:00:00');
        [$owner, $seller] = $this->ownerAndSeller();
        $category = PackageCategory::factory()->for($owner->company)->create(['name' => 'Mediano', 'commission_rate' => '0.70']);
        $package = $this->createPackage($seller, $category, PackageStatus::Delivered, '2026-10-05 12:00:00');
        $this->actingAs($owner)->post(route('sellers.commission-settlements.store', $seller), [
            'date_from' => '2026-10-01', 'date_to' => '2026-10-07',
        ]);
        $settlement = SellerCommissionSettlement::query()->sole();

        $this->actingAs($owner)->get(route('sellers.commissions.index', $seller))->assertSee($settlement->ulid)->assertSee('Bs 0.70');
        $this->actingAs($owner)->get(route('sellers.commission-settlements.show', [$seller, $settlement]))
            ->assertSee($package->tracking_code)->assertSee('Mediano');
        $this->actingAs($owner)->get(route('reports.commissions.index', [
            'date_from' => '2026-10-08', 'date_to' => '2026-10-08',
        ]))->assertSee($seller->name)->assertSee('Bs 0.70');
    }

    /** @return array{User, Seller} */
    private function ownerAndSeller(): array
    {
        $company = Company::factory()->create();
        $owner = User::factory()->for($company)->withRole(UserRole::Owner)->create();
        $seller = Seller::factory()->for($company)->create();

        return [$owner, $seller];
    }

    private function createPackage(
        Seller $seller,
        PackageCategory $category,
        PackageStatus $status,
        ?string $deliveredAt,
        string $receivedAt = '2026-10-05 09:00:00',
    ): Package {
        return Package::factory()
            ->for($seller->company)
            ->for($seller)
            ->withStatus($status)
            ->create([
                'package_category_id' => $category->id,
                'received_at' => $receivedAt,
                'delivered_at' => $deliveredAt,
            ]);
    }
}
