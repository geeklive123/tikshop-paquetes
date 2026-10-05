<?php

namespace Tests\Feature;

use App\Actions\Packages\CalculatePackageStorageAmountAction;
use App\Enums\PackageStatus;
use App\Models\Package;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class PackageStorageAmountTest extends TestCase
{
    use LazilyRefreshDatabase;

    /** @return array<string, array{string, int, string, string}> */
    public static function storageBoundaries(): array
    {
        return [
            'day 1' => ['2026-10-01 18:00:00', 1, '0.00', '2.00'],
            'day 7' => ['2026-10-07 18:00:00', 7, '0.00', '2.00'],
            'day 8' => ['2026-10-08 00:01:00', 8, '1.00', '3.00'],
            'day 14' => ['2026-10-14 23:59:00', 14, '1.00', '3.00'],
            'day 15' => ['2026-10-15 00:01:00', 15, '2.00', '4.00'],
        ];
    }

    #[DataProvider('storageBoundaries')]
    public function test_calculates_weekly_storage_boundaries(
        string $asOf,
        int $expectedDays,
        string $expectedSurcharge,
        string $expectedTotal,
    ): void {
        $package = Package::factory()->create([
            'storage_price' => '2.00',
            'weekly_storage_increment' => '1.00',
            'received_at' => CarbonImmutable::parse('2026-10-01 10:00:00', 'America/La_Paz'),
        ]);

        $amount = app(CalculatePackageStorageAmountAction::class)->execute(
            $package,
            CarbonImmutable::parse($asOf, 'America/La_Paz'),
        );

        $this->assertSame($expectedDays, $amount['daysStored']);
        $this->assertSame($expectedSurcharge, $amount['surchargeAmount']);
        $this->assertSame($expectedTotal, $amount['totalAmount']);
    }

    public function test_uses_lapaz_calendar_days_near_the_utc_boundary(): void
    {
        $package = Package::factory()->create([
            'storage_price' => '2.00',
            'weekly_storage_increment' => '1.00',
            'received_at' => CarbonImmutable::parse('2026-10-01 23:30:00', 'America/La_Paz'),
        ]);

        $amount = app(CalculatePackageStorageAmountAction::class)->execute(
            $package,
            CarbonImmutable::parse('2026-10-08 00:15:00', 'America/La_Paz'),
        );

        $this->assertSame(8, $amount['daysStored']);
        $this->assertSame('3.00', $amount['totalAmount']);
    }

    public function test_delivered_package_uses_frozen_final_amount(): void
    {
        $package = Package::factory()->withStatus(PackageStatus::Delivered)->create([
            'storage_price' => '2.00',
            'weekly_storage_increment' => '1.00',
            'final_storage_amount' => '3.00',
            'received_at' => '2026-10-01 10:00:00',
            'delivered_at' => '2026-10-08 12:00:00',
        ]);

        $amount = app(CalculatePackageStorageAmountAction::class)->execute(
            $package,
            CarbonImmutable::parse('2026-12-01 12:00:00', 'America/La_Paz'),
        );

        $this->assertSame(8, $amount['daysStored']);
        $this->assertSame('1.00', $amount['surchargeAmount']);
        $this->assertSame('3.00', $amount['totalAmount']);
    }

    public function test_cancelled_package_does_not_add_a_storage_surcharge(): void
    {
        $package = Package::factory()->withStatus(PackageStatus::Cancelled)->create([
            'storage_price' => '2.00',
            'weekly_storage_increment' => '1.00',
            'received_at' => '2026-10-01 10:00:00',
            'cancelled_at' => '2026-10-20 12:00:00',
        ]);

        $amount = app(CalculatePackageStorageAmountAction::class)->execute($package);

        $this->assertSame('0.00', $amount['surchargeAmount']);
        $this->assertSame('2.00', $amount['totalAmount']);
    }
}
