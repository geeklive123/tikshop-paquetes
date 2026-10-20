<?php

namespace Tests\Feature;

use App\Enums\PackageEventType;
use App\Enums\PackagePaymentStatus;
use App\Enums\PackageStatus;
use App\Models\Branch;
use App\Models\Company;
use App\Models\Package;
use App\Models\PackagePickupToken;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class PickupResolutionTest extends TestCase
{
    use LazilyRefreshDatabase;

    /** @return array<string, array{string, int, string, string}> */
    public static function pickupStorageBoundaries(): array
    {
        return [
            'day 1' => ['2026-10-01 18:00:00', 1, '0.00', '2.00'],
            'day 7' => ['2026-10-07 18:00:00', 7, '0.00', '2.00'],
            'day 8' => ['2026-10-08 00:01:00', 8, '1.00', '3.00'],
            'day 14' => ['2026-10-14 23:59:00', 14, '1.00', '3.00'],
            'day 15' => ['2026-10-15 00:01:00', 15, '2.00', '4.00'],
        ];
    }

    #[DataProvider('pickupStorageBoundaries')]
    public function test_qr_shows_inclusive_storage_time_and_current_pending_charge(
        string $now,
        int $expectedDays,
        string $expectedSurcharge,
        string $expectedTotal,
    ): void {
        $this->travelTo(CarbonImmutable::parse($now, 'America/La_Paz'));
        [$user, $package, $rawToken] = $this->pickupContext();
        $package->update([
            'payment_status' => PackagePaymentStatus::Pending,
            'paid_amount' => null,
            'paid_at' => null,
            'paid_by' => null,
            'received_at' => CarbonImmutable::parse('2026-10-01 10:00:00', 'America/La_Paz'),
            'storage_price' => '2.00',
            'weekly_storage_increment' => '1.00',
        ]);

        $response = $this->actingAs($user)->get(route('pickup.show', ['token' => $rawToken]));

        $response->assertOk();
        $response->assertSee("{$expectedDays} ".($expectedDays === 1 ? 'día' : 'días'));
        $response->assertSee('Precio base');
        $response->assertSee('Bs 2.00');
        $response->assertSee('Recargo semanal');
        $response->assertSee("Bs {$expectedSurcharge}");
        $response->assertSee('TOTAL A COBRAR');
        $response->assertSee("Bs {$expectedTotal}");
        $response->assertSee('POR PAGAR');
        $response->assertSee('MARCAR COMO PAGADO');
        $response->assertDontSee('CONFIRMAR ENTREGA');
    }

    public function test_qr_shows_paid_snapshot_and_legacy_without_generating_a_charge(): void
    {
        [$user, $paidPackage, $paidToken] = $this->pickupContext();

        $paidResponse = $this->actingAs($user)->get(route('pickup.show', ['token' => $paidToken]));

        $paidResponse->assertOk();
        $paidResponse->assertSee('PAGADO');
        $paidResponse->assertSee('Pagado');
        $paidResponse->assertSee('Bs 1.00');
        $paidResponse->assertSee('Fecha');
        $paidResponse->assertSee('Registrado por');
        $paidResponse->assertSee('CONFIRMAR ENTREGA');
        $paidResponse->assertDontSee('TOTAL A COBRAR');

        $legacyPackage = Package::factory()->for($user->company)->withStatus(PackageStatus::ReadyForPickup)->create([
            'payment_status' => PackagePaymentStatus::Legacy,
        ]);
        $legacyToken = str_repeat('L', 64);
        PackagePickupToken::factory()->for($legacyPackage)->create([
            'token_hash' => hash('sha256', $legacyToken),
            'token_encrypted' => $legacyToken,
        ]);

        $legacyResponse = $this->actingAs($user)->get(route('pickup.show', ['token' => $legacyToken]));

        $legacyResponse->assertOk();
        $legacyResponse->assertSee('SIN SEGUIMIENTO HISTÓRICO');
        $legacyResponse->assertSee('Tiempo almacenado');
        $legacyResponse->assertSee('CONFIRMAR ENTREGA');
        $legacyResponse->assertDontSee('TOTAL A COBRAR');
        $legacyResponse->assertDontSee('MARCAR COMO PAGADO');
    }

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

    public function test_delivered_package_token_is_rejected(): void
    {
        [$user, $package, $rawToken] = $this->pickupContext();
        $package->update([
            'status' => PackageStatus::Delivered,
            'delivered_at' => now(),
        ]);

        $response = $this->actingAs($user)->get(route('pickup.show', ['token' => $rawToken]));

        $response->assertUnprocessable();
        $response->assertSee('Este paquete ya fue entregado.');
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
            'payment_status' => PackagePaymentStatus::Paid,
            'paid_amount' => '1.00',
            'paid_at' => now(),
            'paid_by' => $user->id,
        ]);
        $rawToken = str_repeat('A', 64);
        $pickupToken = PackagePickupToken::factory()->for($package)->create([
            'token_hash' => hash('sha256', $rawToken),
            'token_encrypted' => $rawToken,
        ]);

        return [$user, $package, $rawToken, $pickupToken];
    }
}
