<?php

namespace Tests\Feature;

use App\Actions\Packages\GeneratePickupQrCodeAction;
use App\Enums\PackageStatus;
use App\Enums\UserRole;
use App\Models\Branch;
use App\Models\Company;
use App\Models\Package;
use App\Models\PackageCategory;
use App\Models\PackagePickupToken;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class PackagePickupQrSharingTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_png_is_generated_for_an_active_pickup_token(): void
    {
        [$user, $package] = $this->userPackageAndToken();

        $response = $this->actingAs($user)->get(route('packages.pickup-qr.download', $package));

        $response->assertOk();
        $response->assertHeader('content-type', 'image/png');
        $this->assertStringContainsString(
            'attachment; filename="qr-'.$package->tracking_code.'.png"',
            (string) $response->headers->get('content-disposition'),
        );
        $this->assertSame("\x89PNG\r\n\x1a\n", substr($response->getContent(), 0, 8));
        $imageInfo = getimagesizefromstring($response->getContent());
        $this->assertIsArray($imageInfo);
        $this->assertSame('image/png', $imageInfo['mime']);
        $this->assertSame(900, $imageInfo[0]);
        $this->assertSame(1120, $imageInfo[1]);
    }

    public function test_png_uses_the_existing_pickup_token(): void
    {
        [$user, $package, $pickupToken] = $this->userPackageAndToken();
        $rawToken = $pickupToken->token_encrypted;
        $expectedPickupUrl = route('pickup.show', ['token' => $rawToken]);
        $validQrPng = (new GeneratePickupQrCodeAction)->executePng('https://example.test/pickup/token', 560, 12);

        $this->mock(GeneratePickupQrCodeAction::class, function (MockInterface $mock) use ($expectedPickupUrl, $validQrPng): void {
            $mock->shouldReceive('executePng')
                ->once()
                ->with($expectedPickupUrl, 560, 12)
                ->andReturn($validQrPng);
        });

        $this->actingAs($user)
            ->get(route('packages.pickup-qr.download', $package))
            ->assertOk();

        $this->assertModelExists($pickupToken);
    }

    public function test_downloading_png_does_not_create_a_new_pickup_token(): void
    {
        [$user, $package] = $this->userPackageAndToken();

        $this->actingAs($user)->get(route('packages.pickup-qr.download', $package))->assertOk();
        $this->actingAs($user)->get(route('packages.pickup-qr.download', $package))->assertOk();

        $this->assertDatabaseCount('package_pickup_tokens', 1);
    }

    /** @return array<string, array{PackageStatus}> */
    public static function terminalPackageStatuses(): array
    {
        return [
            'delivered' => [PackageStatus::Delivered],
            'cancelled' => [PackageStatus::Cancelled],
        ];
    }

    #[DataProvider('terminalPackageStatuses')]
    public function test_terminal_package_does_not_generate_a_shareable_qr(PackageStatus $status): void
    {
        [$user, $package] = $this->userPackageAndToken(status: $status);

        $response = $this->actingAs($user)
            ->from(route('packages.show', $package))
            ->get(route('packages.pickup-qr.download', $package));

        $response->assertRedirect(route('packages.show', $package));
        $response->assertSessionHasErrors('pickup_qr');
    }

    /** @return array<string, array{string}> */
    public static function invalidPickupTokenStates(): array
    {
        return [
            'used' => ['used'],
            'revoked' => ['revoked'],
            'expired' => ['expired'],
        ];
    }

    #[DataProvider('invalidPickupTokenStates')]
    public function test_inactive_token_does_not_generate_a_shareable_qr(string $state): void
    {
        [$user, $package, $pickupToken] = $this->userPackageAndToken();

        $pickupToken->update(match ($state) {
            'used' => ['used_at' => now()->subMinute()],
            'revoked' => ['revoked_at' => now()->subMinute()],
            'expired' => ['expires_at' => now()->subMinute()],
        });

        $response = $this->actingAs($user)
            ->from(route('packages.show', $package))
            ->get(route('packages.pickup-qr.download', $package));

        $response->assertRedirect(route('packages.show', $package));
        $response->assertSessionHasErrors('pickup_qr');
    }

    public function test_user_from_another_company_cannot_download_the_pickup_qr(): void
    {
        [, $package] = $this->userPackageAndToken();
        $otherCompanyUser = User::factory()->withRole(UserRole::Operator)->create();

        $this->actingAs($otherCompanyUser)
            ->get(route('packages.pickup-qr.download', $package))
            ->assertNotFound();
    }

    public function test_png_reflects_tracking_and_recipient_snapshots(): void
    {
        [$user, $package] = $this->userPackageAndToken();

        $originalPng = $this->actingAs($user)
            ->get(route('packages.pickup-qr.download', $package))
            ->assertOk()
            ->getContent();

        $package->update(['tracking_code' => 'TIK-261002-0015']);
        $trackingPng = $this->actingAs($user)
            ->get(route('packages.pickup-qr.download', $package))
            ->assertOk()
            ->getContent();

        $package->update(['recipient_name' => 'Juan Pérez']);
        $recipientNamePng = $this->actingAs($user)
            ->get(route('packages.pickup-qr.download', $package))
            ->assertOk()
            ->getContent();

        $package->update(['recipient_phone' => '70000000']);
        $recipientPhonePng = $this->actingAs($user)
            ->get(route('packages.pickup-qr.download', $package))
            ->assertOk()
            ->getContent();

        $this->assertNotSame($originalPng, $trackingPng);
        $this->assertNotSame($trackingPng, $recipientNamePng);
        $this->assertNotSame($recipientNamePng, $recipientPhonePng);
    }

    public function test_png_reflects_the_configured_branch_address(): void
    {
        [$user, $package] = $this->userPackageAndToken();
        $package->branch->update(['address' => 'Calle Configurada 123']);

        $configuredAddressPng = $this->actingAs($user)
            ->get(route('packages.pickup-qr.download', $package))
            ->assertOk()
            ->getContent();

        $package->branch->update(['address' => 'Avenida Alternativa 456']);
        $otherAddressPng = $this->actingAs($user)
            ->get(route('packages.pickup-qr.download', $package))
            ->assertOk()
            ->getContent();

        $this->assertNotSame($configuredAddressPng, $otherAddressPng);
    }

    public function test_png_uses_the_fallback_address_when_branch_address_is_empty(): void
    {
        [$user, $package] = $this->userPackageAndToken();
        $fallbackAddress = 'Ayacucho y General Acha, al lado de Entel - Edificio Galindo, 2do piso';
        $package->branch->update(['address' => null]);

        $fallbackPng = $this->actingAs($user)
            ->get(route('packages.pickup-qr.download', $package))
            ->assertOk()
            ->getContent();

        $package->branch->update(['address' => $fallbackAddress]);
        $configuredFallbackPng = $this->actingAs($user)
            ->get(route('packages.pickup-qr.download', $package))
            ->assertOk()
            ->getContent();

        $this->assertSame($fallbackPng, $configuredFallbackPng);
    }

    public function test_png_does_not_reflect_internal_storage_information(): void
    {
        [$user, $package] = $this->userPackageAndToken();

        $originalPng = $this->actingAs($user)
            ->get(route('packages.pickup-qr.download', $package))
            ->assertOk()
            ->getContent();

        $package->update([
            'storage_code' => 'ANOTHER-SECRET-STORAGE',
            'storage_price' => '184.30',
            'weekly_storage_increment' => '81.20',
            'final_storage_amount' => '999.00',
            'notes' => 'OTRA NOTA INTERNA SECRETA',
        ]);

        $updatedPng = $this->actingAs($user)
            ->get(route('packages.pickup-qr.download', $package))
            ->assertOk()
            ->getContent();

        $this->assertSame($originalPng, $updatedPng);
    }

    public function test_whatsapp_share_uses_recipient_phone_without_modifying_it(): void
    {
        [$user, $package, $pickupToken] = $this->userPackageAndToken(recipientPhone: '7123-4567');
        $expectedMessage = rawurlencode('Hola, tienes un paquete listo para recoger en Tik Shop. Presenta tu QR al momento de recogerlo.');

        $response = $this->actingAs($user)->get(route('packages.show', $package));

        $response->assertOk();
        $response->assertSee('data-whatsapp-url="https://wa.me/59171234567?text='.$expectedMessage.'"', false);
        $response->assertSee('Descargar QR');
        $response->assertSee('Compartir por WhatsApp');
        $response->assertDontSee($pickupToken->token_encrypted, false);
        $this->assertSame('7123-4567', $package->fresh()->recipient_phone);
    }

    public function test_png_response_has_sensitive_no_store_headers(): void
    {
        [$user, $package] = $this->userPackageAndToken();

        $response = $this->actingAs($user)->get(route('packages.pickup-qr.download', $package));

        $response->assertOk();
        $this->assertStringContainsString('no-store', (string) $response->headers->get('cache-control'));
        $response->assertHeader('pragma', 'no-cache');
        $response->assertHeader('referrer-policy', 'no-referrer');
    }

    public function test_pickup_qr_download_requires_authentication(): void
    {
        [, $package] = $this->userPackageAndToken();

        $this->get(route('packages.pickup-qr.download', $package))
            ->assertRedirect(route('login'));
    }

    /** @return array{User, Package, PackagePickupToken} */
    private function userPackageAndToken(
        PackageStatus $status = PackageStatus::ReadyForPickup,
        string $recipientPhone = '71234567',
    ): array {
        $company = Company::factory()->create();
        $branch = Branch::factory()->for($company)->create();
        $category = PackageCategory::factory()->for($company)->create();
        $user = User::factory()->for($company)->withRole(UserRole::Operator)->create();
        $package = Package::factory()->forBranch($branch)->withStatus($status)->create([
            'package_category_id' => $category->id,
            'storage_code' => 'SECRET-STORAGE-42',
            'storage_price' => '99.75',
            'notes' => 'NOTA INTERNA SECRETA',
            'recipient_name' => 'María del Carmen López',
            'recipient_phone' => $recipientPhone,
            'received_by' => $user->id,
        ]);
        $pickupToken = PackagePickupToken::factory()->for($package)->create();

        return [$user, $package, $pickupToken];
    }
}
