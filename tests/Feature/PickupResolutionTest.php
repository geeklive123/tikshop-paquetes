<?php

namespace Tests\Feature;

use App\Enums\PackageEventType;
use App\Enums\PackageStatus;
use App\Models\Branch;
use App\Models\Company;
use App\Models\Package;
use App\Models\PackagePickupToken;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class PickupResolutionTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_authenticated_user_can_open_mobile_scanner(): void
    {
        [$user] = $this->pickupContext();

        $response = $this->actingAs($user)->get(route('pickup.scanner'));

        $response->assertOk();
        $response->assertSee('ESCANEAR CÓDIGO QR');
        $response->assertSee('data-qr-scanner', escape: false);
        $response->assertSee('Ingresar código manualmente');
    }

    public function test_unauthenticated_user_cannot_open_scanner(): void
    {
        $response = $this->get(route('pickup.scanner'));

        $response->assertRedirect(route('login'));
    }

    public function test_valid_qr_url_resolves_package_without_delivering_it(): void
    {
        [$user, $package, $rawToken] = $this->pickupContext();
        $package->update(['storage_code' => 'P3']);
        $categoryName = $package->category->name;
        $qrUrl = route('pickup.show', ['token' => $rawToken]);

        $response = $this->actingAs($user)->post(route('pickup.resolve'), ['code' => $qrUrl]);

        $response->assertOk();
        $response->assertSee('PAQUETE ENCONTRADO');
        $response->assertSee($package->tracking_code);
        $response->assertSee('UBICACIÓN');
        $response->assertSee('P3');
        $response->assertSee($categoryName);
        $response->assertSee('CONFIRMAR ENTREGA');
        $this->assertSame(PackageStatus::ReadyForPickup, $package->fresh()->status);
        $this->assertDatabaseHas('package_events', [
            'package_id' => $package->id,
            'user_id' => $user->id,
            'event' => PackageEventType::QrScanned->value,
        ]);
    }

    public function test_qr_response_prevents_caching_and_referrer_leaks(): void
    {
        [$user, , $rawToken] = $this->pickupContext();

        $response = $this->actingAs($user)->get(route('pickup.show', ['token' => $rawToken]));

        $cacheControl = (string) $response->headers->get('Cache-Control');

        $this->assertStringContainsString('no-store', $cacheControl);
        $this->assertStringContainsString('no-cache', $cacheControl);
        $this->assertStringContainsString('must-revalidate', $cacheControl);
        $response->assertHeader('Pragma', 'no-cache');
        $response->assertHeader('Referrer-Policy', 'no-referrer');
    }

    public function test_nonexistent_token_is_rejected(): void
    {
        [$user] = $this->pickupContext();

        $response = $this->actingAs($user)->get(route('pickup.show', ['token' => str_repeat('Z', 64)]));

        $response->assertUnprocessable();
        $response->assertSee('El código QR no es válido.');
    }

    public function test_revoked_token_is_rejected(): void
    {
        [$user, , $rawToken, $pickupToken] = $this->pickupContext();
        $pickupToken->update(['revoked_at' => now()]);

        $response = $this->actingAs($user)->get(route('pickup.show', ['token' => $rawToken]));

        $response->assertUnprocessable();
        $response->assertSee('Este código fue reemplazado y ya no es válido.');
    }

    public function test_used_token_is_rejected_with_clear_message(): void
    {
        [$user, , $rawToken, $pickupToken] = $this->pickupContext();
        $pickupToken->update(['used_at' => now()]);

        $response = $this->actingAs($user)->get(route('pickup.show', ['token' => $rawToken]));

        $response->assertUnprocessable();
        $response->assertSee('Este código ya fue utilizado.');
    }

    public function test_expired_token_is_rejected(): void
    {
        [$user, , $rawToken, $pickupToken] = $this->pickupContext();
        $pickupToken->update(['expires_at' => now()->subMinute()]);

        $response = $this->actingAs($user)->get(route('pickup.show', ['token' => $rawToken]));

        $response->assertUnprocessable();
        $response->assertSee('Este código QR ha expirado.');
    }

    public function test_cancelled_package_token_is_rejected(): void
    {
        [$user, $package, $rawToken] = $this->pickupContext();
        $package->update(['status' => PackageStatus::Cancelled]);

        $response = $this->actingAs($user)->get(route('pickup.show', ['token' => $rawToken]));

        $response->assertUnprocessable();
        $response->assertSee('Este paquete fue cancelado y no puede entregarse.');
    }

    public function test_other_company_cannot_resolve_token(): void
    {
        [, , $rawToken] = $this->pickupContext();
        $otherUser = User::factory()->create();

        $response = $this->actingAs($otherUser)->get(route('pickup.show', ['token' => $rawToken]));

        $response->assertNotFound();
    }

    public function test_manual_search_only_finds_package_from_users_company(): void
    {
        [$user, $package] = $this->pickupContext();
        $otherPackage = Package::factory()->create(['tracking_code' => 'TIK-260905-9999']);

        $ownResponse = $this->actingAs($user)->post(route('pickup.manual'), [
            'tracking_code' => strtolower($package->tracking_code),
        ]);
        $otherResponse = $this->actingAs($user)->post(route('pickup.manual'), [
            'tracking_code' => $otherPackage->tracking_code,
        ]);

        $ownResponse->assertOk();
        $ownResponse->assertSee($package->tracking_code);
        $ownResponse->assertSee('CONFIRMAR ENTREGA');
        $ownResponse->assertSee(route('packages.deliver', $package), escape: false);
        $otherResponse->assertOk();
        $otherResponse->assertSee('Paquete no encontrado');
    }

    /** @return array{User, Package, string, PackagePickupToken} */
    private function pickupContext(): array
    {
        $company = Company::factory()->create();
        $branch = Branch::factory()->for($company)->create();
        $user = User::factory()->for($company)->create();
        $package = Package::factory()->forBranch($branch)->withStatus(PackageStatus::ReadyForPickup)->create([
            'ready_at' => now(),
            'received_by' => $user->id,
        ]);
        $rawToken = str_repeat('A', 64);
        $pickupToken = PackagePickupToken::factory()->for($package)->create([
            'token_hash' => hash('sha256', $rawToken),
            'token_encrypted' => $rawToken,
        ]);

        return [$user, $package, $rawToken, $pickupToken];
    }
}
