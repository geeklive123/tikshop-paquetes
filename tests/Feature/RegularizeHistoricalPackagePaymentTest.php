<?php

namespace Tests\Feature;

use App\Actions\Packages\RegularizeHistoricalPackagePaymentAction;
use App\Enums\PackageEventType;
use App\Enums\PackagePaymentStatus;
use App\Enums\PackageStatus;
use App\Enums\UserRole;
use App\Models\Company;
use App\Models\Package;
use App\Models\PackagePickupToken;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Validation\ValidationException;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class RegularizeHistoricalPackagePaymentTest extends TestCase
{
    use LazilyRefreshDatabase;

    private const string Source = 'client_confirmation';

    public function test_pending_historical_payment_uses_explicit_amount_and_preserves_package_identity(): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-10-20 11:30:00', 'America/La_Paz'));
        [$owner, $package] = $this->paymentContext();
        $pickupToken = PackagePickupToken::factory()->for($package)->create();
        $originalTrackingCode = $package->tracking_code;

        $regularized = app(RegularizeHistoricalPackagePaymentAction::class)->execute(
            $owner,
            $package,
            '2',
            $this->historicalDate(),
            self::Source,
        );

        $regularized->refresh();
        $this->assertSame(PackagePaymentStatus::Paid, $regularized->payment_status);
        $this->assertSame('2.00', $regularized->paid_amount);
        $this->assertSame('2026-10-14 00:00:00', $regularized->paid_at?->format('Y-m-d H:i:s'));
        $this->assertSame($owner->id, $regularized->paid_by);
        $this->assertSame($originalTrackingCode, $regularized->tracking_code);
        $this->assertSame(PackageStatus::ReadyForPickup, $regularized->status);

        $pickupToken->refresh();
        $this->assertNull($pickupToken->used_at);
        $this->assertNull($pickupToken->revoked_at);
        $this->assertDatabaseCount('package_pickup_tokens', 1);

        $event = $regularized->events()->sole();
        $this->assertSame(PackageEventType::PackageHistoricalPaymentRegularized, $event->event);
        $this->assertSame($owner->id, $event->user_id);
        $this->assertSame('2026-10-20 11:30:00', $event->created_at?->format('Y-m-d H:i:s'));
        $this->assertSame([
            'historical' => true,
            'source' => self::Source,
            'confirmed_date' => '2026-10-14',
            'exact_time_unknown' => true,
            'paid_amount' => '2.00',
            'previous_payment_status' => PackagePaymentStatus::Pending->value,
            'regularized_at' => '2026-10-20T15:30:00.000000Z',
        ], $event->metadata);
    }

    public function test_legacy_delivered_package_becomes_historically_paid_without_changing_operational_state(): void
    {
        [$admin, $package] = $this->paymentContext(UserRole::Admin, [
            'status' => PackageStatus::Delivered,
            'payment_status' => PackagePaymentStatus::Legacy,
            'delivered_at' => '2026-10-13 16:00:00',
            'delivered_by' => null,
            'final_storage_amount' => '99.00',
        ]);

        app(RegularizeHistoricalPackagePaymentAction::class)->execute(
            $admin,
            $package,
            '3.00',
            $this->historicalDate(),
            self::Source,
        );

        $package->refresh();
        $this->assertSame(PackagePaymentStatus::Paid, $package->payment_status);
        $this->assertSame(PackageStatus::Delivered, $package->status);
        $this->assertSame('3.00', $package->paid_amount);
        $this->assertSame('99.00', $package->final_storage_amount);
        $this->assertSame(PackagePaymentStatus::Legacy->value, $package->events()->sole()->metadata['previous_payment_status']);
    }

    public function test_repeating_identical_regularization_is_idempotent(): void
    {
        [$owner, $package] = $this->paymentContext();
        $action = app(RegularizeHistoricalPackagePaymentAction::class);

        $action->execute($owner, $package, '2.00', $this->historicalDate(), self::Source);
        $firstPayment = $package->fresh();
        $action->execute($owner, $package, '2.00', $this->historicalDate(), self::Source);

        $secondPayment = $package->fresh();
        $this->assertSame($firstPayment->paid_amount, $secondPayment->paid_amount);
        $this->assertSame($firstPayment->paid_at?->toISOString(), $secondPayment->paid_at?->toISOString());
        $this->assertSame($firstPayment->paid_by, $secondPayment->paid_by);
        $this->assertSame(1, $package->events()->where('event', PackageEventType::PackageHistoricalPaymentRegularized)->count());
    }

    public function test_existing_paid_package_cannot_be_overwritten(): void
    {
        [$owner, $package] = $this->paymentContext(attributes: [
            'payment_status' => PackagePaymentStatus::Paid,
            'paid_amount' => '8.00',
            'paid_at' => '2026-10-10 10:00:00',
        ]);

        try {
            app(RegularizeHistoricalPackagePaymentAction::class)->execute(
                $owner,
                $package,
                '2.00',
                $this->historicalDate(),
                self::Source,
            );
            $this->fail('Expected validation exception was not thrown.');
        } catch (ValidationException $exception) {
            $this->assertSame('El paquete ya tiene un pago registrado y no puede sobrescribirse.', $exception->validator->errors()->first('payment'));
        }

        $package->refresh();
        $this->assertSame('8.00', $package->paid_amount);
        $this->assertSame('2026-10-10', $package->paid_at?->toDateString());
        $this->assertDatabaseCount('package_events', 0);
    }

    public function test_cancelled_package_cannot_be_regularized(): void
    {
        [$owner, $package] = $this->paymentContext(attributes: [
            'status' => PackageStatus::Cancelled,
            'payment_status' => PackagePaymentStatus::Legacy,
            'cancelled_at' => '2026-10-15 10:00:00',
        ]);

        $this->expectException(ValidationException::class);

        app(RegularizeHistoricalPackagePaymentAction::class)->execute(
            $owner,
            $package,
            '2.00',
            $this->historicalDate(),
            self::Source,
        );
    }

    public function test_package_from_another_company_is_not_found(): void
    {
        [$owner] = $this->paymentContext();
        $otherPackage = Package::factory()->create();

        $this->expectException(ModelNotFoundException::class);

        app(RegularizeHistoricalPackagePaymentAction::class)->execute(
            $owner,
            $otherPackage,
            '2.00',
            $this->historicalDate(),
            self::Source,
        );
    }

    public function test_operator_cannot_regularize_historical_payment(): void
    {
        [$operator, $package] = $this->paymentContext(UserRole::Operator);

        $this->expectException(AuthorizationException::class);

        app(RegularizeHistoricalPackagePaymentAction::class)->execute(
            $operator,
            $package,
            '2.00',
            $this->historicalDate(),
            self::Source,
        );
    }

    /** @return array<string, array{string, string}> */
    public static function invalidHistoricalPayments(): array
    {
        return [
            'zero amount' => ['0.00', '2026-10-14'],
            'negative amount' => ['-1.00', '2026-10-14'],
            'more than two decimals' => ['2.001', '2026-10-14'],
            'future date' => ['2.00', '2026-10-21'],
            'before reception' => ['2.00', '2026-09-21'],
        ];
    }

    #[DataProvider('invalidHistoricalPayments')]
    public function test_invalid_amount_or_date_is_rejected(string $amount, string $paidDate): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-10-20 10:00:00', 'America/La_Paz'));
        [$owner, $package] = $this->paymentContext();

        try {
            app(RegularizeHistoricalPackagePaymentAction::class)->execute(
                $owner,
                $package,
                $amount,
                CarbonImmutable::parse($paidDate, 'America/La_Paz'),
                self::Source,
            );
            $this->fail('Expected validation exception was not thrown.');
        } catch (ValidationException) {
            $this->assertSame(PackagePaymentStatus::Pending, $package->fresh()->payment_status);
            $this->assertDatabaseCount('package_events', 0);
        }
    }

    public function test_reports_attribute_regularized_payment_to_historical_date_only(): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-10-20 15:00:00', 'America/La_Paz'));
        [$owner, $package] = $this->paymentContext();
        app(RegularizeHistoricalPackagePaymentAction::class)->execute(
            $owner,
            $package,
            '3.00',
            $this->historicalDate(),
            self::Source,
        );

        $historicalReport = $this->actingAs($owner)->get(route('reports.index', [
            'period' => 'custom',
            'date_from' => '2026-10-14',
            'date_to' => '2026-10-14',
        ]));
        $todayReport = $this->actingAs($owner)->get(route('reports.index', [
            'period' => 'custom',
            'date_from' => '2026-10-20',
            'date_to' => '2026-10-20',
        ]));

        $historicalReport->assertViewHas('metrics', fn (array $metrics): bool => $metrics['paid'] === 1 && $metrics['paidAmount'] === 3.0);
        $todayReport->assertViewHas('metrics', fn (array $metrics): bool => $metrics['paid'] === 0 && $metrics['paidAmount'] === 0.0);
    }

    /** @return array{User, Package} */
    private function paymentContext(UserRole $role = UserRole::Owner, array $attributes = []): array
    {
        $company = Company::factory()->create();
        $user = User::factory()->for($company)->withRole($role)->create();
        $package = Package::factory()->for($company)->withStatus(PackageStatus::ReadyForPickup)->create([
            'tracking_code' => 'TIK-260922-0011',
            'received_at' => '2026-09-22 10:00:00',
            'payment_status' => PackagePaymentStatus::Pending,
            ...$attributes,
        ]);

        return [$user, $package];
    }

    private function historicalDate(): CarbonImmutable
    {
        return CarbonImmutable::parse('2026-10-14 00:00:00', 'America/La_Paz');
    }
}
