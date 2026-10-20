<?php

namespace Tests\Feature;

use App\Enums\PackageEventType;
use App\Enums\PackagePaymentStatus;
use App\Enums\PackageStatus;
use App\Enums\UserRole;
use App\Models\Company;
use App\Models\Package;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class RegularizeHistoricalPackagePaymentsCommandTest extends TestCase
{
    use LazilyRefreshDatabase;

    /** @var array<string, string> */
    private const array ConfirmedPayments = [
        'TIK-260922-0011' => '2.00',
        'TIK-260922-0018' => '3.00',
        'TIK-261012-0001' => '3.00',
        'TIK-261012-0003' => '3.00',
        'TIK-261012-0004' => '3.00',
        'TIK-261012-0005' => '3.00',
        'TIK-261012-0007' => '2.00',
        'TIK-261014-0002' => '2.00',
        'TIK-261012-0012' => '3.00',
        'TIK-260925-0001' => '3.00',
    ];

    public function test_without_execute_option_command_validates_and_rolls_back_all_changes(): void
    {
        [$company, $owner] = $this->batchContext();

        $this->artisan('packages:regularize-historical-payments', $this->commandOptions($company, $owner))
            ->expectsOutputToContain('Simulación válida: 10 por regularizar')
            ->assertExitCode(Command::SUCCESS);

        $this->assertSame(0, Package::query()->where('payment_status', PackagePaymentStatus::Paid)->count());
        $this->assertDatabaseCount('package_events', 0);
    }

    public function test_production_without_force_production_is_rejected(): void
    {
        $this->useProductionEnvironment();
        [$company, $owner] = $this->batchContext();

        $this->artisan('packages:regularize-historical-payments', [
            ...$this->commandOptions($company, $owner),
            '--execute' => true,
        ])
            ->expectsOutputToContain('En producción debes proporcionar explícitamente --force-production.')
            ->assertExitCode(Command::FAILURE);

        $this->assertSame(0, Package::query()->where('payment_status', PackagePaymentStatus::Paid)->count());
        $this->assertDatabaseCount('package_events', 0);
    }

    public function test_production_with_force_production_without_execute_only_simulates(): void
    {
        $this->useProductionEnvironment();
        [$company, $owner] = $this->batchContext();

        $this->artisan('packages:regularize-historical-payments', [
            ...$this->commandOptions($company, $owner),
            '--force-production' => true,
        ])
            ->expectsOutputToContain('Total explícito: 10 paquetes')
            ->expectsOutputToContain('Simulación válida: 10 por regularizar')
            ->assertExitCode(Command::SUCCESS);

        $this->assertSame(0, Package::query()->where('payment_status', PackagePaymentStatus::Paid)->count());
        $this->assertDatabaseCount('package_events', 0);
    }

    public function test_production_with_both_flags_and_confirmation_regularizes_only_explicit_packages(): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-10-20 16:00:00', 'America/La_Paz'));
        $this->useProductionEnvironment();
        [$company, $owner, $excludedCancelled, $laterPackage] = $this->batchContext();

        $this->artisan('packages:regularize-historical-payments', [
            ...$this->commandOptions($company, $owner),
            '--execute' => true,
            '--force-production' => true,
        ])
            ->expectsOutputToContain('Total explícito: 10 paquetes')
            ->expectsConfirmation('PRODUCCIÓN: ¿Confirmas regularizar 10 paquetes por Bs 27.00 con fecha 2026-10-14?', 'yes')
            ->expectsOutputToContain('Regularización completada: 10 actualizados, 0 ya idempotentes, total confirmado Bs 27.00.')
            ->assertExitCode(Command::SUCCESS);

        $regularizedPackages = Package::query()->whereIn('tracking_code', array_keys(self::ConfirmedPayments))->get();
        $this->assertSame(10, $regularizedPackages->count());
        $this->assertTrue($regularizedPackages->every(fn (Package $package): bool => $package->payment_status === PackagePaymentStatus::Paid));
        $this->assertTrue($regularizedPackages->every(fn (Package $package): bool => $package->paid_at?->toDateString() === '2026-10-14'));
        $this->assertSame(27.0, (float) $regularizedPackages->sum('paid_amount'));
        $this->assertSame(10, Package::query()
            ->whereIn('tracking_code', array_keys(self::ConfirmedPayments))
            ->whereHas('events', fn ($query) => $query->where('event', PackageEventType::PackageHistoricalPaymentRegularized))
            ->count());

        $this->assertSame(PackagePaymentStatus::Legacy, $excludedCancelled->fresh()->payment_status);
        $this->assertSame(PackageStatus::Cancelled, $excludedCancelled->fresh()->status);
        $this->assertSame(PackagePaymentStatus::Pending, $laterPackage->fresh()->payment_status);
        $this->assertDatabaseCount('packages', 12);
    }

    public function test_second_production_execution_is_idempotent(): void
    {
        $this->useProductionEnvironment();
        [$company, $owner] = $this->batchContext();
        $options = [
            ...$this->commandOptions($company, $owner),
            '--execute' => true,
            '--force-production' => true,
        ];
        $confirmation = 'PRODUCCIÓN: ¿Confirmas regularizar 10 paquetes por Bs 27.00 con fecha 2026-10-14?';

        $this->artisan('packages:regularize-historical-payments', $options)
            ->expectsConfirmation($confirmation, 'yes')
            ->assertExitCode(Command::SUCCESS);
        $this->artisan('packages:regularize-historical-payments', $options)
            ->expectsConfirmation($confirmation, 'yes')
            ->expectsOutputToContain('Regularización completada: 0 actualizados, 10 ya idempotentes, total confirmado Bs 27.00.')
            ->assertExitCode(Command::SUCCESS);

        $this->assertSame(10, Package::query()->where('payment_status', PackagePaymentStatus::Paid)->count());
        $this->assertSame(10, Package::query()
            ->whereHas('events', fn ($query) => $query->where('event', PackageEventType::PackageHistoricalPaymentRegularized))
            ->count());
    }

    /** @return array{Company, User, Package, Package} */
    private function batchContext(): array
    {
        $company = Company::factory()->create();
        $owner = User::factory()->for($company)->withRole(UserRole::Owner)->create([
            'email' => 'owner@tikshop.test',
        ]);

        foreach (self::ConfirmedPayments as $trackingCode => $amount) {
            $isDeliveredLegacy = in_array($trackingCode, ['TIK-261012-0012', 'TIK-260925-0001'], true);
            Package::factory()->for($company)->withStatus(
                $isDeliveredLegacy ? PackageStatus::Delivered : PackageStatus::ReadyForPickup,
            )->create([
                'tracking_code' => $trackingCode,
                'received_at' => str_starts_with($trackingCode, 'TIK-261014') ? '2026-10-14 08:00:00' : '2026-09-22 08:00:00',
                'payment_status' => $isDeliveredLegacy ? PackagePaymentStatus::Legacy : PackagePaymentStatus::Pending,
                'delivered_at' => $isDeliveredLegacy ? '2026-10-14 10:00:00' : null,
                'paid_amount' => null,
                'paid_at' => null,
                'paid_by' => null,
            ]);
        }

        $excludedCancelled = Package::factory()->for($company)->withStatus(PackageStatus::Cancelled)->create([
            'tracking_code' => 'TIK-261014-0004',
            'received_at' => '2026-10-14 09:00:00',
            'cancelled_at' => '2026-10-15 09:00:00',
            'payment_status' => PackagePaymentStatus::Legacy,
        ]);
        $laterPackage = Package::factory()->for($company)->withStatus(PackageStatus::ReadyForPickup)->create([
            'tracking_code' => 'TIK-261015-0001',
            'received_at' => '2026-10-15 09:00:00',
            'payment_status' => PackagePaymentStatus::Pending,
        ]);

        return [$company, $owner, $excludedCancelled, $laterPackage];
    }

    /** @return array<string, mixed> */
    private function commandOptions(Company $company, User $owner): array
    {
        return [
            '--company' => (string) $company->id,
            '--actor-email' => $owner->email,
            '--paid-date' => '2026-10-14',
            '--source' => 'client_confirmation',
            '--payment' => array_map(
                fn (string $amount, string $trackingCode): string => "{$trackingCode}:{$amount}",
                self::ConfirmedPayments,
                array_keys(self::ConfirmedPayments),
            ),
        ];
    }

    private function useProductionEnvironment(): void
    {
        $this->app->detectEnvironment(fn (): string => 'production');
    }
}
