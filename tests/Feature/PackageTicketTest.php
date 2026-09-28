<?php

namespace Tests\Feature;

use App\Actions\Packages\ResolveTicketLogoAction;
use App\Enums\UserRole;
use App\Models\Branch;
use App\Models\Company;
use App\Models\Package;
use App\Models\PackageCategory;
use App\Models\PackagePickupToken;
use App\Models\Printer;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\File;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class PackageTicketTest extends TestCase
{
    use LazilyRefreshDatabase;

    private const string FALLBACK_ADDRESS = 'Ayacucho y General Acha, al lado de Entel - Edificio Galindo, 2do piso';

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

    public function test_package_ticket_generates_with_local_logo_available(): void
    {
        [$user, $package] = $this->userAndPackage(UserRole::Operator);
        $logoDataUri = app(ResolveTicketLogoAction::class)->execute();

        $response = $this->actingAs($user)->get(route('packages.ticket', $package));

        $this->assertIsString($logoDataUri);
        $this->assertNotSame('', $logoDataUri);
        $this->assertStringStartsWith('data:image/png;base64,', $logoDataUri);
        $logoPng = base64_decode(substr($logoDataUri, strlen('data:image/png;base64,')), true);
        $this->assertIsString($logoPng);
        $this->assertSame("\x89PNG\r\n\x1a\n", substr($logoPng, 0, 8));
        $logoInfo = getimagesizefromstring($logoPng);
        $this->assertIsArray($logoInfo);
        $this->assertSame('image/png', $logoInfo['mime']);
        $this->assertSame(320, $logoInfo[0]);
        $this->assertSame(320, $logoInfo[1]);
        $this->assertSame(file_get_contents(config('tickets.logo_path')), $logoPng);
        $logoImage = imagecreatefromstring($logoPng);
        $this->assertInstanceOf(\GdImage::class, $logoImage);
        $nonWhitePixels = 0;

        for ($y = 0; $y < imagesy($logoImage); $y++) {
            for ($x = 0; $x < imagesx($logoImage); $x++) {
                $pixel = imagecolorat($logoImage, $x, $y);
                $red = ($pixel >> 16) & 0xFF;
                $green = ($pixel >> 8) & 0xFF;
                $blue = $pixel & 0xFF;

                if ($red < 245 || $green < 245 || $blue < 245) {
                    $nonWhitePixels++;
                }
            }
        }

        imagedestroy($logoImage);
        $this->assertGreaterThan(10000, $nonWhitePixels);
        $response->assertOk();
        $this->assertStringStartsWith('%PDF', $response->getContent());
        $this->assertGreaterThanOrEqual(2, substr_count($response->getContent(), '/Subtype /Image'));
        $this->assertMatchesRegularExpression('/\/Subtype \/Image\s*\/Width 320\s*\/Height 320/s', $response->getContent());
    }

    public function test_package_ticket_still_generates_when_logo_does_not_exist(): void
    {
        [$user, $package] = $this->userAndPackage(UserRole::Operator);
        Config::set('tickets.logo_path', storage_path('missing/tikshop-logo.webp'));
        $this->assertNull(app(ResolveTicketLogoAction::class)->execute());
        $package->load(['company:id,name', 'branch:id,name,address', 'category:id,name']);
        $fallbackTicket = view('packages.ticket', [
            'package' => $package,
            'qrImagePath' => null,
            'logoDataUri' => null,
        ])->render();

        $response = $this->actingAs($user)->get(route('packages.ticket', $package));

        $this->assertStringContainsString('<div class="brand">Tik Shop</div>', $fallbackTicket);
        $response->assertOk();
        $response->assertHeader('content-type', 'application/pdf');
        $this->assertStringStartsWith('%PDF', $response->getContent());
    }

    public function test_package_ticket_uses_80_millimeter_paper_width(): void
    {
        [$user, $package] = $this->userAndPackage(UserRole::Operator);

        $response = $this->actingAs($user)->get(route('packages.ticket', $package));

        $response->assertOk();
        $this->assertStringContainsString('/MediaBox [0.000 0.000 226.770 510.240]', $response->getContent());
    }

    public function test_package_ticket_uses_fallback_address_when_branch_address_is_empty(): void
    {
        [, $package] = $this->userAndPackage(UserRole::Operator);
        $package->branch->update(['address' => null]);
        $package->load(['company:id,name', 'branch:id,name,address', 'category:id,name']);

        $ticket = view('packages.ticket', [
            'package' => $package,
            'qrImagePath' => null,
            'logoDataUri' => null,
        ])->render();

        $this->assertStringContainsString(self::FALLBACK_ADDRESS, $ticket);
        $this->assertSame(1, substr_count($ticket, self::FALLBACK_ADDRESS));
    }

    public function test_package_ticket_prefers_configured_branch_address_without_duplicating_fallback(): void
    {
        [, $package] = $this->userAndPackage(UserRole::Operator);
        $package->branch->update(['address' => 'Calle Propia 123']);
        $package->load(['company:id,name', 'branch:id,name,address', 'category:id,name']);

        $ticket = view('packages.ticket', [
            'package' => $package,
            'qrImagePath' => null,
            'logoDataUri' => null,
        ])->render();

        $this->assertStringContainsString('Calle Propia 123', $ticket);
        $this->assertStringNotContainsString(self::FALLBACK_ADDRESS, $ticket);
        $this->assertSame(1, substr_count($ticket, 'Calle Propia 123'));
    }

    public function test_print_ticket_button_appears_for_active_default_printer_without_claiming_automatic_printing(): void
    {
        [$user, $package] = $this->userAndPackage(UserRole::Operator);
        Printer::factory()->forBranch($package->branch)->default()->create();

        $response = $this->actingAs($user)->get(route('packages.show', $package));

        $response->assertSee('Imprimir ticket');
        $response->assertSee('El agente local lo procesará; esta pantalla no imprime directamente.');
        $response->assertSee(route('packages.print-ticket', $package), false);
        $response->assertSee('Ver PDF');
        $response->assertSee('Descargar');
    }

    public function test_print_ticket_button_is_hidden_without_active_default_printer(): void
    {
        [$user, $package] = $this->userAndPackage(UserRole::Operator);
        Printer::factory()->forBranch($package->branch)->default()->create(['active' => false]);

        $response = $this->actingAs($user)->get(route('packages.show', $package));

        $response->assertDontSee('Imprimir ticket');
        $response->assertSee('Ver PDF');
        $response->assertSee('Descargar');
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
