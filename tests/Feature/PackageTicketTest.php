<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Branch;
use App\Models\Company;
use App\Models\Package;
use App\Models\PackageCategory;
use App\Models\PackagePickupToken;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\File;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class PackageTicketTest extends TestCase
{
    use LazilyRefreshDatabase;

    /** @return array<string, array{UserRole}> */
    public static function packageRoles(): array
    {
        return [
            'owner' => [UserRole::Owner],
            'admin' => [UserRole::Admin],
            'operator' => [UserRole::Operator],
        ];
    }

    #[DataProvider('packageRoles')]
    public function test_authorized_role_can_generate_package_ticket_pdf(UserRole $role): void
    {
        [$user, $package] = $this->userAndPackage($role);

        $response = $this->actingAs($user)->get(route('packages.ticket', $package));

        $response->assertOk();
        $response->assertHeader('content-type', 'application/pdf');
        $this->assertStringStartsWith('%PDF', $response->getContent());
        $this->assertStringContainsString('/Subtype /Image', $response->getContent());
        $this->assertDatabaseCount('package_pickup_tokens', 1);
    }

    public function test_package_ticket_download_uses_attachment_disposition(): void
    {
        [$user, $package] = $this->userAndPackage(UserRole::Operator);
        $pickupToken = $package->pickupTokens()->sole();

        $response = $this->actingAs($user)->get(route('packages.ticket.download', $package));

        $response->assertOk();
        $this->assertStringContainsString('attachment', (string) $response->headers->get('content-disposition'));
        $this->assertModelExists($pickupToken);
        $this->assertDatabaseCount('package_pickup_tokens', 1);
    }

    public function test_package_ticket_reuses_the_existing_active_pickup_token(): void
    {
        [$user, $package] = $this->userAndPackage(UserRole::Operator);

        $this->actingAs($user)->get(route('packages.ticket', $package))->assertOk();
        $this->actingAs($user)->get(route('packages.ticket', $package))->assertOk();

        $this->assertDatabaseCount('package_pickup_tokens', 1);
    }

    public function test_package_ticket_does_not_generate_a_new_pickup_token(): void
    {
        [$user, $package] = $this->userAndPackage(UserRole::Operator);
        $package->pickupTokens()->delete();

        $response = $this->actingAs($user)->get(route('packages.ticket', $package));

        $response->assertNotFound();
        $this->assertDatabaseCount('package_pickup_tokens', 0);
    }

    public function test_package_ticket_uses_a_local_png_and_deletes_it_after_rendering(): void
    {
        [$user, $package] = $this->userAndPackage(UserRole::Operator);
        $temporaryQrPath = null;

        File::partialMock()
            ->shouldReceive('put')
            ->once()
            ->withArgs(function (string $path, string $contents) use (&$temporaryQrPath): bool {
                $temporaryQrPath = $path;

                $this->assertSame("\x89PNG\r\n\x1a\n", substr($contents, 0, 8));
                $this->assertStringStartsWith(storage_path('app/tmp'), $path);

                return true;
            })
            ->passthru();

        $response = $this->actingAs($user)->get(route('packages.ticket', $package));

        $response->assertOk();
        $this->assertStringContainsString('/Subtype /Image', $response->getContent());
        $this->assertIsString($temporaryQrPath);
        $this->assertFileDoesNotExist($temporaryQrPath);
    }

    public function test_package_ticket_requires_authentication(): void
    {
        [, $package] = $this->userAndPackage(UserRole::Operator);

        $response = $this->get(route('packages.ticket', $package));

        $response->assertRedirect(route('login'));
    }

    public function test_package_ticket_from_another_company_is_not_found(): void
    {
        [$user] = $this->userAndPackage(UserRole::Operator);
        [, $otherPackage] = $this->userAndPackage(UserRole::Operator);

        $response = $this->actingAs($user)->get(route('packages.ticket', $otherPackage));

        $response->assertNotFound();
    }

    /** @return array{User, Package} */
    private function userAndPackage(UserRole $role): array
    {
        $company = Company::factory()->create();
        $branch = Branch::factory()->for($company)->create();
        $category = PackageCategory::factory()->for($company)->create([
            'name' => 'Grande',
            'price' => '5.00',
        ]);
        $user = User::factory()->for($company)->withRole($role)->create();
        $package = Package::factory()->forBranch($branch)->create([
            'package_category_id' => $category->id,
            'storage_code' => 'G4-02',
            'storage_price' => '5.00',
            'received_by' => $user->id,
        ]);
        PackagePickupToken::factory()->for($package)->create();

        return [$user, $package];
    }
}
