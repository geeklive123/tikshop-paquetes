<?php

namespace Tests\Feature;

use App\Enums\PackageEventType;
use App\Enums\PackageStatus;
use App\Enums\UserRole;
use App\Models\Branch;
use App\Models\Company;
use App\Models\Package;
use App\Models\PackagePickupToken;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class CancelPackageTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_owner_cancels_package_without_deleting_it_and_revokes_active_qr(): void
    {
        $this->travelTo('2026-09-27 16:30:00');
        [$owner, $package, $pickupToken] = $this->cancellationContext();

        $response = $this->actingAs($owner)->post(route('packages.cancel', $package), [
            'reason' => 'Registrado por error',
        ]);

        $response->assertRedirect(route('packages.show', $package));
        $response->assertSessionHas('status', 'Paquete anulado correctamente.');
        $this->assertModelExists($package);
        $package->refresh();
        $this->assertSame(PackageStatus::Cancelled, $package->status);
        $this->assertSame('2026-09-27 16:30:00', $package->cancelled_at->format('Y-m-d H:i:s'));
        $this->assertSame($owner->id, $package->cancelled_by);
        $this->assertSame('Registrado por error', $package->cancellation_reason);
        $this->assertSame('2026-09-27 16:30:00', $pickupToken->fresh()->revoked_at->format('Y-m-d H:i:s'));
        $event = $package->events()->where('event', PackageEventType::PackageCancelled)->sole();
        $this->assertSame($owner->id, $event->user_id);
        $this->assertSame('Registrado por error', $event->metadata['reason']);
        $this->assertSame(1, $event->metadata['revoked_pickup_tokens']);
    }

    public function test_cancellation_near_midnight_keeps_the_bolivian_business_day(): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-09-30 23:58:00', 'America/La_Paz'));
        [$owner, $package, $pickupToken] = $this->cancellationContext();

        $this->actingAs($owner)->post(route('packages.cancel', $package), ['reason' => 'Registro duplicado'])->assertRedirect();

        $this->assertSame('2026-09-30 23:58:00', $package->fresh()->cancelled_at->format('Y-m-d H:i:s'));
        $this->assertSame('America/La_Paz', $package->fresh()->cancelled_at->timezoneName);
        $this->assertSame('2026-09-30 23:58:00', $pickupToken->fresh()->revoked_at->format('Y-m-d H:i:s'));
    }

    public function test_cancellation_requires_a_reason(): void
    {
        [$owner, $package, $pickupToken] = $this->cancellationContext();

        $response = $this->actingAs($owner)->post(route('packages.cancel', $package), ['reason' => '   ']);

        $response->assertSessionHasErrors('reason');
        $this->assertSame(PackageStatus::ReadyForPickup, $package->fresh()->status);
        $this->assertNull($pickupToken->fresh()->revoked_at);
    }

    public function test_cancelled_package_cannot_be_delivered_after_cancellation(): void
    {
        [$owner, $package, $pickupToken] = $this->cancellationContext();
        $this->actingAs($owner)->post(route('packages.cancel', $package), ['reason' => 'Duplicado'])->assertRedirect();

        $response = $this->actingAs($owner)->post(route('packages.deliver', $package));

        $response->assertSessionHasErrors('token');
        $this->assertSame(PackageStatus::Cancelled, $package->fresh()->status);
        $this->assertNull($pickupToken->fresh()->used_at);
        $this->assertNotNull($pickupToken->fresh()->revoked_at);
        $this->assertDatabaseMissing('package_events', [
            'package_id' => $package->id,
            'event' => PackageEventType::PackageDelivered->value,
        ]);
    }

    public function test_delivered_package_cannot_be_cancelled(): void
    {
        [$owner, $package, $pickupToken] = $this->cancellationContext(PackageStatus::Delivered);

        $response = $this->actingAs($owner)->post(route('packages.cancel', $package), ['reason' => 'No válido']);

        $response->assertForbidden();
        $this->assertSame(PackageStatus::Delivered, $package->fresh()->status);
        $this->assertNull($pickupToken->fresh()->revoked_at);
    }

    public function test_operator_cannot_cancel_package(): void
    {
        [$owner, $package, $pickupToken] = $this->cancellationContext();
        $operator = User::factory()->for($owner->company)->withRole(UserRole::Operator)->create();

        $this->actingAs($operator)
            ->post(route('packages.cancel', $package), ['reason' => 'Sin permiso'])
            ->assertForbidden();

        $this->assertSame(PackageStatus::ReadyForPickup, $package->fresh()->status);
        $this->assertNull($pickupToken->fresh()->revoked_at);
    }

    public function test_other_company_cannot_cancel_package(): void
    {
        [, $package, $pickupToken] = $this->cancellationContext();
        $otherOwner = User::factory()->withRole(UserRole::Owner)->create();

        $this->actingAs($otherOwner)
            ->post(route('packages.cancel', $package), ['reason' => 'Fuera de empresa'])
            ->assertNotFound();

        $this->assertSame(PackageStatus::ReadyForPickup, $package->fresh()->status);
        $this->assertNull($pickupToken->fresh()->revoked_at);
    }

    /** @return array{User, Package, PackagePickupToken} */
    private function cancellationContext(PackageStatus $status = PackageStatus::ReadyForPickup): array
    {
        $company = Company::factory()->create();
        $branch = Branch::factory()->for($company)->create();
        $owner = User::factory()->for($company)->withRole(UserRole::Owner)->create();
        $package = Package::factory()->forBranch($branch)->withStatus($status)->create([
            'received_by' => $owner->id,
        ]);
        $pickupToken = PackagePickupToken::factory()->for($package)->create();

        return [$owner, $package, $pickupToken];
    }
}
