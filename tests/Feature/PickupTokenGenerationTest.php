<?php

namespace Tests\Feature;

use App\Actions\Packages\GeneratePickupTokenAction;
use App\Actions\Packages\ResolvePickupTokenAction;
use App\Enums\PackageEventType;
use App\Enums\PackageStatus;
use App\Enums\UserRole;
use App\Models\Branch;
use App\Models\Company;
use App\Models\Package;
use App\Models\PackagePickupToken;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Validation\ValidationException;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class PickupTokenGenerationTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_secure_token_is_hashed_and_package_becomes_ready_for_pickup(): void
    {
        $this->travelTo('2026-09-05 09:15:00');
        [$owner, $package] = $this->userAndPackage(UserRole::Owner);

        $result = app(GeneratePickupTokenAction::class)->execute($owner, $package);

        $this->assertMatchesRegularExpression('/\A[A-Za-z0-9]{64}\z/', $result['rawToken']);
        $this->assertSame(hash('sha256', $result['rawToken']), $result['pickupToken']->token_hash);
        $this->assertNotSame($result['rawToken'], $result['pickupToken']->token_hash);
        $this->assertDatabaseMissing('package_pickup_tokens', ['token_hash' => $result['rawToken']]);
        $this->assertSame(PackageStatus::ReadyForPickup, $result['package']->status);
        $this->assertSame('2026-09-05 09:15:00', $result['package']->ready_at->format('Y-m-d H:i:s'));
        $this->assertDatabaseHas('package_events', [
            'package_id' => $package->id,
            'user_id' => $owner->id,
            'event' => PackageEventType::QrGenerated->value,
        ]);
    }

    public function test_regeneration_revokes_previous_token_and_records_audit_event(): void
    {
        [$owner, $package] = $this->userAndPackage(UserRole::Owner);
        $firstResult = app(GeneratePickupTokenAction::class)->execute($owner, $package);

        $response = $this->actingAs($owner)->post(route('packages.regenerate-qr', $package));

        $response->assertRedirect(route('packages.success', $package));
        $response->assertSessionHas('pickup_token', function (mixed $encryptedToken): bool {
            if (! is_string($encryptedToken) || preg_match('/\A[A-Za-z0-9]{64}\z/', $encryptedToken) === 1) {
                return false;
            }

            $activeTokenHash = PackagePickupToken::query()
                ->whereNull('revoked_at')
                ->sole()
                ->token_hash;

            return hash('sha256', Crypt::decryptString($encryptedToken)) === $activeTokenHash;
        });
        $this->assertNotNull($firstResult['pickupToken']->fresh()->revoked_at);
        $this->assertDatabaseCount('package_pickup_tokens', 2);
        $this->assertDatabaseHas('package_events', [
            'package_id' => $package->id,
            'event' => PackageEventType::QrRegenerated->value,
        ]);

        try {
            app(ResolvePickupTokenAction::class)->execute($owner, $firstResult['rawToken']);
            $this->fail('The revoked token should not resolve.');
        } catch (ValidationException $exception) {
            $this->assertSame('Este código fue reemplazado y ya no es válido.', $exception->validator->errors()->first('token'));
        }
    }

    public function test_operator_cannot_regenerate_pickup_qr(): void
    {
        [$operator, $package] = $this->userAndPackage(UserRole::Operator);

        $response = $this->actingAs($operator)->post(route('packages.regenerate-qr', $package));

        $response->assertForbidden();
        $this->assertDatabaseCount('package_pickup_tokens', 0);
    }

    public function test_admin_can_regenerate_pickup_qr(): void
    {
        [$admin, $package] = $this->userAndPackage(UserRole::Admin);
        app(GeneratePickupTokenAction::class)->execute($admin, $package);

        $response = $this->actingAs($admin)->post(route('packages.regenerate-qr', $package));

        $response->assertRedirect(route('packages.success', $package));
        $this->assertDatabaseCount('package_pickup_tokens', 2);
    }

    /** @return array<string, array{PackageStatus}> */
    public static function terminalStatuses(): array
    {
        return [
            'delivered' => [PackageStatus::Delivered],
            'cancelled' => [PackageStatus::Cancelled],
        ];
    }

    #[DataProvider('terminalStatuses')]
    public function test_terminal_package_cannot_generate_pickup_token(PackageStatus $status): void
    {
        [$owner, $package] = $this->userAndPackage(UserRole::Owner, $status);

        $this->expectException(ValidationException::class);

        app(GeneratePickupTokenAction::class)->execute($owner, $package);
    }

    /** @return array{User, Package} */
    private function userAndPackage(UserRole $role, PackageStatus $status = PackageStatus::Received): array
    {
        $company = Company::factory()->create();
        $branch = Branch::factory()->for($company)->create();
        $user = User::factory()->for($company)->withRole($role)->create();
        $package = Package::factory()->forBranch($branch)->withStatus($status)->create([
            'received_by' => $user->id,
        ]);

        return [$user, $package];
    }
}
