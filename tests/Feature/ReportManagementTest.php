<?php

namespace Tests\Feature;

use App\Enums\PackageStatus;
use App\Enums\UserRole;
use App\Models\Company;
use App\Models\Package;
use App\Models\PackageCategory;
use App\Models\Seller;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class ReportManagementTest extends TestCase
{
    use LazilyRefreshDatabase;

    /** @return array<string, array{UserRole}> */
    public static function reportRoles(): array
    {
        return ['owner' => [UserRole::Owner], 'admin' => [UserRole::Admin]];
    }

    public function test_application_uses_bolivia_timezone(): void
    {
        $this->assertSame('America/La_Paz', config('app.timezone'));
        $this->assertSame('America/La_Paz', date_default_timezone_get());
    }

    #[DataProvider('reportRoles')]
    public function test_owner_and_admin_access_reports(UserRole $role): void
    {
        $user = User::factory()->withRole($role)->create();

        $response = $this->actingAs($user)->get(route('reports.index'));

        $response->assertSee('Reportes');
        $response->assertSee('Monto de almacenaje registrado');
    }

    public function test_operator_receives_forbidden_and_does_not_see_reports_menu(): void
    {
        $operator = User::factory()->withRole(UserRole::Operator)->create();

        $this->actingAs($operator)->get(route('reports.index'))->assertForbidden();
        $this->actingAs($operator)->get(route('dashboard'))->assertDontSee(route('reports.index'));
    }

    public function test_summary_is_strictly_isolated_by_company(): void
    {
        $this->travelTo('2026-09-28 12:00:00');
        $company = Company::factory()->create();
        $owner = User::factory()->for($company)->withRole(UserRole::Owner)->create();
        Package::factory()->for($company)->create(['received_at' => now(), 'storage_price' => '8.00']);
        Package::factory()->create(['received_at' => now(), 'storage_price' => '99.00']);

        $response = $this->actingAs($owner)->get(route('reports.index', ['period' => 'today']));

        $response->assertViewHas('metrics', fn (array $metrics): bool => $metrics['received'] === 1
            && $metrics['storageAmount'] === 8.0);
    }

    /** @return array<string, array{string, string, string}> */
    public static function presetPeriods(): array
    {
        return [
            'today' => ['today', '2026-09-28 08:00:00', '2026-09-27 23:59:59'],
            'this week' => ['this_week', '2026-09-28 00:00:00', '2026-09-27 23:59:59'],
            'this month' => ['this_month', '2026-09-01 00:00:00', '2026-08-31 23:59:59'],
        ];
    }

    #[DataProvider('presetPeriods')]
    public function test_preset_period_filters_include_only_the_expected_packages(string $period, string $inside, string $outside): void
    {
        $this->travelTo('2026-09-28 12:00:00');
        $owner = User::factory()->withRole(UserRole::Owner)->create();
        Package::factory()->for($owner->company)->create(['received_at' => $inside]);
        Package::factory()->for($owner->company)->create(['received_at' => $outside]);

        $response = $this->actingAs($owner)->get(route('reports.index', ['period' => $period]));

        $response->assertViewHas('metrics', fn (array $metrics): bool => $metrics['received'] === 1);
    }

    public function test_custom_range_includes_both_complete_boundary_days(): void
    {
        $owner = User::factory()->withRole(UserRole::Owner)->create();
        foreach (['2026-09-10 00:00:00', '2026-09-11 23:59:59'] as $receivedAt) {
            Package::factory()->for($owner->company)->create(['received_at' => $receivedAt]);
        }
        foreach (['2026-09-09 23:59:59', '2026-09-12 00:00:00'] as $receivedAt) {
            Package::factory()->for($owner->company)->create(['received_at' => $receivedAt]);
        }

        $response = $this->actingAs($owner)->get(route('reports.index', ['date_from' => '2026-09-10', 'date_to' => '2026-09-11']));

        $response->assertViewHas('metrics', fn (array $metrics): bool => $metrics['received'] === 2);
        $response->assertViewHas('range', fn (array $range): bool => $range['start']->timezoneName === 'America/La_Paz'
            && $range['start']->format('Y-m-d H:i:s') === '2026-09-10 00:00:00'
            && $range['end']->timezoneName === 'America/La_Paz'
            && $range['end']->format('Y-m-d H:i:s') === '2026-09-11 23:59:59');
    }

    public function test_today_near_midnight_uses_the_bolivian_calendar_day(): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-09-30 23:30:00', 'America/La_Paz'));
        $owner = User::factory()->withRole(UserRole::Owner)->create();
        Package::factory()->for($owner->company)->create(['tracking_code' => 'TIK-BO-DAY-001', 'received_at' => '2026-09-30 23:45:00']);
        Package::factory()->for($owner->company)->create(['tracking_code' => 'TIK-BO-DAY-002', 'received_at' => '2026-10-01 00:00:00']);

        $response = $this->actingAs($owner)->get(route('reports.packages.index', ['period' => 'today']));

        $response->assertSee('TIK-BO-DAY-001');
        $response->assertDontSee('TIK-BO-DAY-002');
        $response->assertViewHas('range', fn (array $range): bool => $range['dateFrom'] === '2026-09-30'
            && $range['dateTo'] === '2026-09-30');
    }

    public function test_summary_counts_statuses_amount_and_active_sellers_correctly(): void
    {
        $company = Company::factory()->create();
        $owner = User::factory()->for($company)->withRole(UserRole::Owner)->create();
        $firstSeller = Seller::factory()->for($company)->create();
        $secondSeller = Seller::factory()->for($company)->create();
        foreach ([
            [PackageStatus::Received, '10.00', $firstSeller],
            [PackageStatus::ReadyForPickup, '20.00', $firstSeller],
            [PackageStatus::Delivered, '30.00', $secondSeller],
            [PackageStatus::Cancelled, '40.00', $secondSeller],
        ] as [$status, $price, $seller]) {
            Package::factory()->for($company)->for($seller)->withStatus($status)->create(['received_at' => '2026-09-15 12:00:00', 'storage_price' => $price]);
        }

        $response = $this->actingAs($owner)->get(route('reports.index', ['date_from' => '2026-09-01', 'date_to' => '2026-09-30']));

        $response->assertViewHas('metrics', fn (array $metrics): bool => $metrics === [
            'received' => 4, 'pending' => 2, 'delivered' => 1, 'cancelled' => 1,
            'storageAmount' => 60.0, 'activeSellers' => 2,
        ]);
    }

    public function test_package_report_applies_all_business_filters(): void
    {
        $company = Company::factory()->create();
        $admin = User::factory()->for($company)->withRole(UserRole::Admin)->create();
        $seller = Seller::factory()->for($company)->create();
        $otherSeller = Seller::factory()->for($company)->create();
        $category = PackageCategory::factory()->for($company)->create();
        $otherCategory = PackageCategory::factory()->for($company)->create();
        $base = ['received_at' => '2026-09-15 10:00:00', 'seller_id' => $seller->id, 'package_category_id' => $category->id, 'status' => PackageStatus::Delivered, 'tracking_code' => 'TIK-REPORT-001', 'recipient_name' => 'Ana Reporte'];
        Package::factory()->for($company)->create($base);
        Package::factory()->for($company)->create([...$base, 'seller_id' => $otherSeller->id, 'tracking_code' => 'TIK-REPORT-002']);
        Package::factory()->for($company)->create([...$base, 'status' => PackageStatus::Received, 'tracking_code' => 'TIK-REPORT-003']);
        Package::factory()->for($company)->create([...$base, 'package_category_id' => $otherCategory->id, 'tracking_code' => 'TIK-REPORT-004']);
        Package::factory()->for($company)->create([...$base, 'tracking_code' => 'OTRO-CODIGO-01']);
        Package::factory()->for($company)->create([...$base, 'tracking_code' => 'TIK-REPORT-005', 'recipient_name' => 'Otra Persona']);

        $response = $this->actingAs($admin)->get(route('reports.packages.index', [
            'date_from' => '2026-09-01', 'date_to' => '2026-09-30', 'seller_id' => $seller->id,
            'status' => PackageStatus::Delivered->value, 'category_id' => $category->id,
            'tracking_code' => 'REPORT-001', 'recipient' => 'Ana',
        ]));

        $response->assertSee('TIK-REPORT-001');
        foreach (['TIK-REPORT-002', 'TIK-REPORT-003', 'TIK-REPORT-004', 'OTRO-CODIGO-01', 'TIK-REPORT-005'] as $hiddenTracking) {
            $response->assertDontSee($hiddenTracking);
        }
        $response->assertViewHas('packages', fn ($packages): bool => $packages->total() === 1);
    }

    public function test_package_report_rejects_filter_id_from_another_company(): void
    {
        $owner = User::factory()->withRole(UserRole::Owner)->create();
        $otherSeller = Seller::factory()->create();

        $this->actingAs($owner)->get(route('reports.packages.index', ['seller_id' => $otherSeller->id]))
            ->assertSessionHasErrors('seller_id');
    }

    public function test_seller_report_has_correct_counts_and_excludes_cancelled_amount(): void
    {
        $company = Company::factory()->create();
        $owner = User::factory()->for($company)->withRole(UserRole::Owner)->create();
        $seller = Seller::factory()->for($company)->create(['name' => 'Vendedora Reportada', 'business_name' => 'Tienda Uno']);
        foreach ([[PackageStatus::Received, '10.00'], [PackageStatus::ReadyForPickup, '20.00'], [PackageStatus::Delivered, '30.00'], [PackageStatus::Cancelled, '90.00']] as [$status, $price]) {
            Package::factory()->for($company)->for($seller)->withStatus($status)->create(['received_at' => '2026-09-15', 'storage_price' => $price]);
        }

        $response = $this->actingAs($owner)->get(route('reports.sellers.index', ['date_from' => '2026-09-01', 'date_to' => '2026-09-30']));

        $response->assertSee('Vendedora Reportada');
        $response->assertSee('No representa una comisión calculada.');
        $response->assertViewHas('sellers', function ($sellers) use ($seller): bool {
            $row = $sellers->getCollection()->firstWhere('id', $seller->id);

            return $row !== null && $row->total_packages === 4 && $row->pending_packages === 2
                && $row->delivered_packages === 1 && $row->cancelled_packages === 1
                && (float) $row->storage_amount === 60.0;
        });
    }

    public function test_seller_detail_is_paginated_and_cross_company_seller_is_not_found(): void
    {
        $company = Company::factory()->create();
        $owner = User::factory()->for($company)->withRole(UserRole::Owner)->create();
        $seller = Seller::factory()->for($company)->create();
        Package::factory()->count(16)->for($company)->for($seller)->create(['received_at' => '2026-09-15']);
        $otherSeller = Seller::factory()->create();

        $firstPage = $this->actingAs($owner)->get(route('reports.sellers.show', ['seller' => $seller, 'date_from' => '2026-09-01', 'date_to' => '2026-09-30']));
        $secondPage = $this->actingAs($owner)->get(route('reports.sellers.show', ['seller' => $seller, 'date_from' => '2026-09-01', 'date_to' => '2026-09-30', 'page' => 2]));

        $firstPage->assertViewHas('packages', fn ($packages): bool => $packages->count() === 15 && $packages->total() === 16);
        $secondPage->assertViewHas('packages', fn ($packages): bool => $packages->count() === 1 && $packages->currentPage() === 2);
        $this->actingAs($owner)->get(route('reports.sellers.show', $otherSeller))->assertNotFound();
    }

    public function test_cancellation_report_shows_reason_user_and_applies_filters(): void
    {
        $company = Company::factory()->create();
        $owner = User::factory()->for($company)->withRole(UserRole::Owner)->create();
        $seller = Seller::factory()->for($company)->create();
        $otherSeller = Seller::factory()->for($company)->create();
        $cancellingUser = User::factory()->for($company)->withRole(UserRole::Admin)->create(['name' => 'Ana Administradora']);
        Package::factory()->for($company)->for($seller)->withStatus(PackageStatus::Cancelled)->create(['tracking_code' => 'TIK-CANCEL-001', 'cancellation_reason' => 'Registro duplicado', 'cancelled_by' => $cancellingUser->id, 'cancelled_at' => '2026-09-20 18:00:00']);
        Package::factory()->for($company)->for($otherSeller)->withStatus(PackageStatus::Cancelled)->create(['tracking_code' => 'TIK-CANCEL-002', 'cancelled_at' => '2026-09-20 18:00:00']);

        $response = $this->actingAs($owner)->get(route('reports.cancellations.index', ['date_from' => '2026-09-20', 'date_to' => '2026-09-20', 'seller_id' => $seller->id, 'cancelled_by' => $cancellingUser->id]));

        $response->assertSee('TIK-CANCEL-001');
        $response->assertSee('Registro duplicado');
        $response->assertSee('Ana Administradora');
        $response->assertDontSee('TIK-CANCEL-002');
    }

    public function test_package_report_is_paginated_and_hides_other_company_packages(): void
    {
        $this->travelTo('2026-09-28 12:00:00');
        $company = Company::factory()->create();
        $owner = User::factory()->for($company)->withRole(UserRole::Owner)->create();
        Package::factory()->count(16)->for($company)->create(['received_at' => now()]);
        Package::factory()->create(['tracking_code' => 'TIK-SECRET-001', 'received_at' => now()]);

        $response = $this->actingAs($owner)->get(route('reports.packages.index', ['period' => 'today']));

        $response->assertDontSee('TIK-SECRET-001');
        $response->assertViewHas('packages', fn ($packages): bool => $packages->count() === 15 && $packages->total() === 16 && $packages->hasMorePages());
    }

    public function test_custom_range_requires_valid_ordered_dates(): void
    {
        $owner = User::factory()->withRole(UserRole::Owner)->create();

        $this->actingAs($owner)->get(route('reports.index', ['period' => 'custom', 'date_from' => '2026-09-20', 'date_to' => '2026-09-10']))
            ->assertSessionHasErrors('date_to');
    }
}
