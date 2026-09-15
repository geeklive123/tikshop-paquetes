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
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class DeliverPackageTest extends TestCase
{
    use LazilyRefreshDatabase;

    /** @return array<string, array{UserRole}> */
    public static function deliveryRoles(): array
    {
        return [
            'owner' => [UserRole::Owner],
            'admin' => [UserRole::Admin],
            'operator' => [UserRole::Operator],
        ];
    }

    #[DataProvider('deliveryRoles')]
    public function test_authorized_role_confirms_delivery_with_valid_qr(UserRole $role): void
    {
        $this->travelTo('2026-09-05 14:20:00');
        [$user, $package, $rawToken, $pickupToken] = $this->pickupContext($role);

        $response = $this->actingAs($user)->post(route('pickup.deliver'), ['token' => $rawToken]);

        $response->assertRedirect(route('packages.show', $package));
        $response->assertSessionHas('status', 'Paquete entregado correctamente.');
        $package->refresh();
        $this->assertSame(PackageStatus::Delivered, $package->status);
        $this->assertSame('2026-09-05 14:20:00', $package->delivered_at->format('Y-m-d H:i:s'));
        $this->assertSame($user->id, $package->delivered_by);
        $this->assertSame('2026-09-05 14:20:00', $pickupToken->fresh()->used_at->format('Y-m-d H:i:s'));
        $this->assertDatabaseHas('package_events', [
            'package_id' => $package->id,
            'user_id' => $user->id,
            'event' => PackageEventType::PackageDelivered->value,
        ]);
    }

    public function test_other_company_cannot_confirm_delivery(): void
    {
        [, $package, $rawToken, $pickupToken] = $this->pickupContext(UserRole::Operator);
        $otherUser = User::factory()->withRole(UserRole::Owner)->create();

        $response = $this->actingAs($otherUser)->post(route('pickup.deliver'), ['token' => $rawToken]);

        $response->assertNotFound();
        $this->assertSame(PackageStatus::ReadyForPickup, $package->fresh()->status);
        $this->assertNull($pickupToken->fresh()->used_at);
        $this->assertDatabaseMissing('package_events', [
            'package_id' => $package->id,
            'event' => PackageEventType::PackageDelivered->value,
        ]);
    }

    public function test_cancelled_package_cannot_be_delivered(): void
    {
        [$user, $package, $rawToken, $pickupToken] = $this->pickupContext(UserRole::Admin);
        $package->update(['status' => PackageStatus::Cancelled, 'cancelled_at' => now()]);

        $response = $this->actingAs($user)->post(route('pickup.deliver'), ['token' => $rawToken]);

        $response->assertSessionHasErrors('token');
        $this->assertSame(PackageStatus::Cancelled, $package->fresh()->status);
        $this->assertNull($pickupToken->fresh()->used_at);
    }

    public function test_delivered_package_cannot_be_delivered_again(): void
    {
        [$user, $package, $rawToken] = $this->pickupContext(UserRole::Owner);

        $firstResponse = $this->actingAs($user)->post(route('pickup.deliver'), ['token' => $rawToken]);
        $secondResponse = $this->actingAs($user)->post(route('pickup.deliver'), ['token' => $rawToken]);

        $firstResponse->assertRedirect(route('packages.show', $package));
        $secondResponse->assertSessionHasErrors('token');
        $this->assertDatabaseCount('package_events', 1);
        $this->assertSame(PackageStatus::Delivered, $package->fresh()->status);
    }

    public function test_revoked_token_cannot_confirm_delivery(): void
    {
        [$user, $package, $rawToken, $pickupToken] = $this->pickupContext(UserRole::Operator);
        $pickupToken->update(['revoked_at' => now()]);

        $response = $this->actingAs($user)->post(route('pickup.deliver'), ['token' => $rawToken]);

        $response->assertSessionHasErrors('token');
        $this->assertSame(PackageStatus::ReadyForPickup, $package->fresh()->status);
        $this->assertNull($pickupToken->fresh()->used_at);
    }

    public function test_manual_delivery_uses_delivery_action_and_invalidates_active_qr(): void
    {
        $this->travelTo('2026-09-05 15:30:00');
        [$user, $package, , $pickupToken] = $this->pickupContext(UserRole::Operator);

        $response = $this->actingAs($user)->post(route('packages.deliver', $package));

        $response->assertRedirect(route('packages.show', $package));
        $response->assertSessionHas('status', 'Paquete entregado correctamente.');
        $package->refresh();
        $this->assertSame(PackageStatus::Delivered, $package->status);
        $this->assertSame('2026-09-05 15:30:00', $package->delivered_at->format('Y-m-d H:i:s'));
        $this->assertSame($user->id, $package->delivered_by);
        $this->assertSame('2026-09-05 15:30:00', $pickupToken->fresh()->used_at->format('Y-m-d H:i:s'));
        $this->assertDatabaseHas('package_events', [
            'package_id' => $package->id,
            'user_id' => $user->id,
            'event' => PackageEventType::PackageDelivered->value,
        ]);
    }

    public function test_manually_delivered_package_cannot_be_delivered_again(): void
    {
        [$user, $package, , $pickupToken] = $this->pickupContext(UserRole::Owner);

        $firstResponse = $this->actingAs($user)->post(route('packages.deliver', $package));
        $secondResponse = $this->actingAs($user)->post(route('packages.deliver', $package));

        $firstResponse->assertRedirect(route('packages.show', $package));
        $secondResponse->assertSessionHasErrors('token');
        $this->assertDatabaseCount('package_events', 1);
        $this->assertSame(PackageStatus::Delivered, $package->fresh()->status);
        $this->assertNotNull($pickupToken->fresh()->used_at);
    }

    public function test_cancelled_package_cannot_be_manually_delivered(): void
    {
        [$user, $package, , $pickupToken] = $this->pickupContext(UserRole::Admin);
        $package->update(['status' => PackageStatus::Cancelled, 'cancelled_at' => now()]);

        $response = $this->actingAs($user)->post(route('packages.deliver', $package));

        $response->assertSessionHasErrors('token');
        $this->assertSame(PackageStatus::Cancelled, $package->fresh()->status);
        $this->assertNull($pickupToken->fresh()->used_at);
        $this->assertDatabaseMissing('package_events', [
            'package_id' => $package->id,
            'event' => PackageEventType::PackageDelivered->value,
        ]);
    }

    public function test_user_without_package_permission_cannot_confirm_manual_delivery(): void
    {
        [, $package, , $pickupToken] = $this->pickupContext(UserRole::Operator);
        $otherUser = User::factory()->withRole(UserRole::Owner)->create();

        $response = $this->actingAs($otherUser)->post(route('packages.deliver', $package));

        $response->assertNotFound();
        $this->assertSame(PackageStatus::ReadyForPickup, $package->fresh()->status);
        $this->assertNull($pickupToken->fresh()->used_at);
        $this->assertDatabaseMissing('package_events', [
            'package_id' => $package->id,
            'event' => PackageEventType::PackageDelivered->value,
        ]);
    }

    /** @return array{User, Package, string, PackagePickupToken} */
    private function pickupContext(UserRole $role): array
    {
        $company = Company::factory()->create();
        $branch = Branch::factory()->for($company)->create();
        $user = User::factory()->for($company)->withRole($role)->create();
        $package = Package::factory()->forBranch($branch)->withStatus(PackageStatus::ReadyForPickup)->create([
            'ready_at' => now(),
            'received_by' => $user->id,
        ]);
        $rawToken = str_repeat('B', 64);
        $pickupToken = PackagePickupToken::factory()->for($package)->create([
            'token_hash' => hash('sha256', $rawToken),
            'token_encrypted' => $rawToken,
        ]);

        return [$user, $package, $rawToken, $pickupToken];
    }
}
