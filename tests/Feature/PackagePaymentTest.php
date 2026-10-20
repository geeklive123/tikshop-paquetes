<?php

namespace Tests\Feature;

use App\Enums\PackageEventType;
use App\Enums\PackagePaymentStatus;
use App\Enums\PackageStatus;
use App\Enums\UserRole;
use App\Models\Branch;
use App\Models\Company;
use App\Models\Package;
use App\Models\PackageCategory;
use App\Models\PackagePickupToken;
use App\Models\Seller;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class PackagePaymentTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_new_package_is_pending_by_default(): void
    {
        [$user, $category, $seller] = $this->creationContext();

        $this->actingAs($user)->post(route('packages.store'), $this->creationPayload($category, $seller))->assertRedirect();

        $package = Package::query()->sole();
        $this->assertSame(PackagePaymentStatus::Pending, $package->payment_status);
        $this->assertNull($package->paid_amount);
        $this->assertNull($package->paid_at);
        $this->assertNull($package->paid_by);
    }

    public function test_new_package_can_be_registered_as_paid_using_the_server_amount(): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-10-20 10:00:00', 'America/La_Paz'));
        [$user, $category, $seller] = $this->creationContext();

        $this->actingAs($user)->post(route('packages.store'), [
            ...$this->creationPayload($category, $seller),
            'payment_status' => PackagePaymentStatus::Paid->value,
            'paid_amount' => '0.01',
        ])->assertRedirect();

        $package = Package::query()->sole();
        $this->assertSame(PackagePaymentStatus::Paid, $package->payment_status);
        $this->assertSame('5.00', $package->paid_amount);
        $this->assertSame('2026-10-20 10:00:00', $package->paid_at?->format('Y-m-d H:i:s'));
        $this->assertSame($user->id, $package->paid_by);
        $this->assertDatabaseHas('package_events', [
            'package_id' => $package->id,
            'event' => PackageEventType::PackagePaymentMarkedPaid->value,
        ]);
    }

    public function test_operator_can_mark_pending_package_as_paid_and_amount_is_frozen(): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-10-08 12:00:00', 'America/La_Paz'));
        [$user, $package] = $this->paymentContext(UserRole::Operator, [
            'received_at' => '2026-10-01 10:00:00',
            'storage_price' => '2.00',
            'weekly_storage_increment' => '1.00',
        ]);

        $this->actingAs($user)->post(route('packages.payment.store', $package))->assertRedirect(route('packages.show', $package));

        $package->refresh();
        $this->assertSame(PackagePaymentStatus::Paid, $package->payment_status);
        $this->assertSame('3.00', $package->paid_amount);
        $this->assertSame('2026-10-08 12:00:00', $package->paid_at?->format('Y-m-d H:i:s'));
        $this->assertSame($user->id, $package->paid_by);
    }

    public function test_marking_as_paid_does_not_consume_or_replace_the_pickup_qr(): void
    {
        [$user, $package] = $this->paymentContext(UserRole::Operator);
        $pickupToken = PackagePickupToken::factory()->for($package)->create();

        $this->actingAs($user)->post(route('packages.payment.store', $package))->assertRedirect();

        $this->assertDatabaseCount('package_pickup_tokens', 1);
        $pickupToken->refresh();
        $this->assertNull($pickupToken->used_at);
        $this->assertNull($pickupToken->revoked_at);
    }

    public function test_marking_paid_twice_is_idempotent(): void
    {
        $this->travelTo('2026-10-08 12:00:00');
        [$user, $package] = $this->paymentContext();

        $this->actingAs($user)->post(route('packages.payment.store', $package));
        $firstPayment = $package->fresh();
        $this->travelTo('2026-10-20 12:00:00');
        $this->actingAs($user)->post(route('packages.payment.store', $package))->assertRedirect();

        $secondPayment = $package->fresh();
        $this->assertSame($firstPayment->paid_amount, $secondPayment->paid_amount);
        $this->assertSame($firstPayment->paid_at?->toISOString(), $secondPayment->paid_at?->toISOString());
        $this->assertSame(1, $package->events()->where('event', PackageEventType::PackagePaymentMarkedPaid)->count());
    }

    public function test_cancelled_and_legacy_packages_cannot_be_marked_paid(): void
    {
        [$user, $cancelled] = $this->paymentContext(attributes: [
            'status' => PackageStatus::Cancelled,
            'cancelled_at' => now(),
        ]);
        $legacy = Package::factory()->for($user->company)->create([
            'payment_status' => PackagePaymentStatus::Legacy,
        ]);

        $this->actingAs($user)->post(route('packages.payment.store', $cancelled))->assertSessionHasErrors('payment');
        $this->actingAs($user)->post(route('packages.payment.store', $legacy))->assertSessionHasErrors('payment');

        $this->assertSame(PackagePaymentStatus::Pending, $cancelled->fresh()->payment_status);
        $this->assertSame(PackagePaymentStatus::Legacy, $legacy->fresh()->payment_status);
    }

    public function test_other_company_cannot_mark_package_as_paid(): void
    {
        [, $package] = $this->paymentContext();
        $otherUser = User::factory()->withRole(UserRole::Owner)->create();

        $this->actingAs($otherUser)->post(route('packages.payment.store', $package))->assertNotFound();

        $this->assertSame(PackagePaymentStatus::Pending, $package->fresh()->payment_status);
    }

    public function test_pending_cannot_be_delivered_but_paid_and_legacy_can(): void
    {
        [$user, $pending] = $this->paymentContext();
        $paid = Package::factory()->for($user->company)->withStatus(PackageStatus::ReadyForPickup)->create([
            'payment_status' => PackagePaymentStatus::Paid,
            'paid_amount' => '2.00',
            'paid_at' => now(),
            'paid_by' => $user->id,
        ]);
        $legacy = Package::factory()->for($user->company)->withStatus(PackageStatus::ReadyForPickup)->create([
            'payment_status' => PackagePaymentStatus::Legacy,
        ]);

        $this->actingAs($user)->post(route('packages.deliver', $pending))->assertSessionHasErrors('token');
        $this->actingAs($user)->post(route('packages.deliver', $paid))->assertRedirect();
        $this->actingAs($user)->post(route('packages.deliver', $legacy))->assertRedirect();

        $this->assertSame(PackageStatus::ReadyForPickup, $pending->fresh()->status);
        $this->assertSame(PackageStatus::Delivered, $paid->fresh()->status);
        $this->assertSame(PackageStatus::Delivered, $legacy->fresh()->status);
    }

    public function test_reports_use_business_dates_and_exclude_legacy_from_financial_totals(): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-10-20 23:30:00', 'America/La_Paz'));
        $company = Company::factory()->create();
        $owner = User::factory()->for($company)->withRole(UserRole::Owner)->create();
        Package::factory()->for($company)->withStatus(PackageStatus::Delivered)->create([
            'received_at' => '2026-10-01 10:00:00',
            'delivered_at' => '2026-10-20 08:00:00',
            'payment_status' => PackagePaymentStatus::Paid,
            'paid_amount' => '7.00',
            'paid_at' => '2026-10-20 07:00:00',
            'paid_by' => $owner->id,
        ]);
        Package::factory()->for($company)->create([
            'received_at' => '2026-10-13 10:00:00',
            'storage_price' => '2.00',
            'weekly_storage_increment' => '1.00',
            'payment_status' => PackagePaymentStatus::Pending,
        ]);
        Package::factory()->for($company)->withStatus(PackageStatus::Cancelled)->create([
            'received_at' => '2026-09-01 10:00:00',
            'cancelled_at' => '2026-10-20 09:00:00',
            'payment_status' => PackagePaymentStatus::Legacy,
        ]);
        Package::factory()->for($company)->withStatus(PackageStatus::Delivered)->create([
            'payment_status' => PackagePaymentStatus::Legacy,
            'paid_amount' => '999.00',
            'paid_at' => null,
        ]);
        Package::factory()->create([
            'payment_status' => PackagePaymentStatus::Paid,
            'paid_amount' => '500.00',
            'paid_at' => '2026-10-20 10:00:00',
        ]);

        $response = $this->actingAs($owner)->get(route('reports.index', ['period' => 'today']));

        $response->assertViewHas('metrics', fn (array $metrics): bool => $metrics['received'] === 0
            && $metrics['delivered'] === 1
            && $metrics['cancelled'] === 1
            && $metrics['paid'] === 1
            && $metrics['paidAmount'] === 7.0
            && $metrics['pending'] === 1
            && $metrics['pendingAmount'] === 3.0
            && $metrics['legacy'] === 2);
        $response->assertSee('No cuentan como deuda ni como pago.');
    }

    /** @return array{User, PackageCategory, Seller} */
    private function creationContext(): array
    {
        $company = Company::factory()->create();
        Branch::factory()->for($company)->create(['name' => (string) config('tikshop.main_branch.name')]);
        $user = User::factory()->for($company)->withRole(UserRole::Operator)->create();
        $category = PackageCategory::factory()->for($company)->create([
            'price' => '5.00',
            'weekly_storage_increment' => '1.00',
        ]);
        $seller = Seller::factory()->for($company)->create();

        return [$user, $category, $seller];
    }

    /** @return array<string, string|int> */
    private function creationPayload(PackageCategory $category, Seller $seller): array
    {
        return [
            'seller_id' => $seller->id,
            'package_category_id' => $category->id,
            'storage_code' => 'A-01',
            'recipient_name' => 'Cliente Pago',
            'recipient_phone' => '70000000',
        ];
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @return array{User, Package}
     */
    private function paymentContext(UserRole $role = UserRole::Owner, array $attributes = []): array
    {
        $company = Company::factory()->create();
        $user = User::factory()->for($company)->withRole($role)->create();
        $package = Package::factory()->for($company)->withStatus(PackageStatus::ReadyForPickup)->create([
            'payment_status' => PackagePaymentStatus::Pending,
            ...$attributes,
        ]);

        return [$user, $package];
    }
}
