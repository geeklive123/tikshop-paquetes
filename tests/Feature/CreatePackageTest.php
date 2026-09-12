<?php

namespace Tests\Feature;

use App\Enums\PackageEventType;
use App\Enums\PackageStatus;
use App\Enums\UserRole;
use App\Models\Branch;
use App\Models\Company;
use App\Models\Package;
use App\Models\PackageCategory;
use App\Models\PackagePickupToken;
use App\Models\Seller;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class CreatePackageTest extends TestCase
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
    public function test_authorized_role_creates_package_with_company_branch_and_audit_data(UserRole $role): void
    {
        $this->travelTo('2026-09-04 10:30:00');
        [$user, $company, $mainBranch, $category] = $this->userWithMainBranch($role);

        $response = $this->actingAs($user)->post(route('packages.store'), $this->validPayload($category));

        $package = Package::query()->sole();
        $response->assertRedirect(route('packages.success', $package));
        $this->assertSame($company->id, $package->company_id);
        $this->assertSame($mainBranch->id, $package->branch_id);
        $this->assertSame($user->id, $package->received_by);
        $this->assertSame($category->id, $package->package_category_id);
        $this->assertSame('S5-11', $package->storage_code);
        $this->assertSame('3.75', $package->storage_price);
        $this->assertSame(PackageStatus::ReadyForPickup, $package->status);
        $this->assertSame('2026-09-04 10:30:00', $package->received_at->format('Y-m-d H:i:s'));
        $this->assertSame('2026-09-04 10:30:00', $package->ready_at->format('Y-m-d H:i:s'));
        $this->assertSame('TIK-260904-0001', $package->tracking_code);
        $this->assertTrue(Str::isUlid($package->ulid));
        $this->assertSame('ulid', $package->getRouteKeyName());
        $this->assertDatabaseHas('package_events', [
            'package_id' => $package->id,
            'user_id' => $user->id,
            'event' => PackageEventType::PackageCreated->value,
        ]);
        $this->assertDatabaseHas('package_events', [
            'package_id' => $package->id,
            'user_id' => $user->id,
            'event' => PackageEventType::QrGenerated->value,
        ]);
    }

    public function test_tracking_code_is_unique_and_increments_within_the_day(): void
    {
        $this->travelTo('2026-09-04 11:00:00');
        [$user, , , $category] = $this->userWithMainBranch(UserRole::Operator);

        $this->actingAs($user)->post(route('packages.store'), $this->validPayload($category));
        $this->actingAs($user)->post(route('packages.store'), $this->validPayload($category));

        $this->assertSame(
            ['TIK-260904-0001', 'TIK-260904-0002'],
            Package::query()->orderBy('id')->pluck('tracking_code')->all(),
        );
    }

    public function test_created_package_flashes_only_an_encrypted_pickup_token(): void
    {
        [$user, , , $category] = $this->userWithMainBranch(UserRole::Operator);

        $response = $this->actingAs($user)->post(route('packages.store'), $this->validPayload($category));

        $response->assertSessionHas('pickup_token', function (mixed $encryptedToken): bool {
            if (! is_string($encryptedToken) || preg_match('/\A[A-Za-z0-9]{64}\z/', $encryptedToken) === 1) {
                return false;
            }

            return hash('sha256', Crypt::decryptString($encryptedToken))
                === PackagePickupToken::query()->sole()->token_hash;
        });
    }

    public function test_unauthenticated_user_cannot_access_packages(): void
    {
        $response = $this->get(route('packages.index'));

        $response->assertRedirect(route('login'));
    }

    public function test_authenticated_user_can_view_the_create_form(): void
    {
        [$user, , , $category] = $this->userWithMainBranch(UserRole::Operator);

        $response = $this->actingAs($user)->get(route('packages.create'));

        $response->assertOk();
        $response->assertSee('VENDEDOR');
        $response->assertSee('DATOS DE QUIEN RECOGERÁ');
        $response->assertSee('INFORMACIÓN DEL PAQUETE');
        $response->assertSee($category->name);
        $response->assertSee('Bs 3.75');
        $response->assertSee('Código / ubicación de almacenaje');
        $response->assertSee('REGISTRAR PAQUETE');
    }

    public function test_success_screen_shows_registered_package_and_expected_actions(): void
    {
        [$user, , , $category] = $this->userWithMainBranch(UserRole::Operator);

        $this->actingAs($user)->post(route('packages.store'), $this->validPayload($category));
        $package = Package::query()->sole();
        $response = $this->actingAs($user)->get(route('packages.success', $package));

        $response->assertOk();
        $response->assertSee('Paquete registrado correctamente');
        $response->assertSee($package->tracking_code);
        $response->assertSee('Ver paquete');
        $response->assertSee('Ver ticket PDF');
        $response->assertSee('Descargar ticket PDF');
        $response->assertSee('Registrar otro paquete');
        $response->assertSee('Volver al listado');
        $response->assertSee('data:image/svg+xml;base64,', escape: false);
        $response->assertSee('S5-11');
    }

    public function test_required_contact_fields_are_validated(): void
    {
        [$user] = $this->userWithMainBranch(UserRole::Operator);

        $response = $this->actingAs($user)->post(route('packages.store'));

        $response->assertSessionHasErrors(['seller_id', 'package_category_id', 'storage_code', 'recipient_name', 'recipient_phone']);
        $this->assertDatabaseCount('packages', 0);
    }

    /** @return array<string, array{string, mixed}> */
    public static function invalidPackageFields(): array
    {
        return [
            'recipient name maximum' => ['recipient_name', str_repeat('a', 151)],
            'recipient phone maximum' => ['recipient_phone', str_repeat('1', 31)],
            'description maximum' => ['description', str_repeat('a', 1001)],
            'notes maximum' => ['notes', str_repeat('a', 2001)],
            'storage code maximum' => ['storage_code', str_repeat('a', 51)],
        ];
    }

    #[DataProvider('invalidPackageFields')]
    public function test_package_fields_reject_values_over_their_maximum(string $field, mixed $value): void
    {
        [$user, , , $category] = $this->userWithMainBranch(UserRole::Operator);
        $payload = [...$this->validPayload($category), $field => $value];

        $response = $this->actingAs($user)->post(route('packages.store'), $payload);

        $response->assertSessionHasErrors($field);
        $this->assertDatabaseCount('packages', 0);
    }

    public function test_package_control_fields_cannot_be_overridden_by_the_request(): void
    {
        [$user, $company, $mainBranch, $category] = $this->userWithMainBranch(UserRole::Operator);
        $otherCompany = Company::factory()->create();
        $otherBranch = Branch::factory()->for($otherCompany)->create();
        $otherUser = User::factory()->for($otherCompany)->create();

        $this->actingAs($user)->post(route('packages.store'), [
            ...$this->validPayload($category),
            'company_id' => $otherCompany->id,
            'branch_id' => $otherBranch->id,
            'tracking_code' => 'TIK-000000-9999',
            'status' => PackageStatus::Delivered->value,
            'received_by' => $otherUser->id,
            'storage_price' => '0.01',
        ]);

        $package = Package::query()->sole();
        $this->assertSame($company->id, $package->company_id);
        $this->assertSame($mainBranch->id, $package->branch_id);
        $this->assertSame($user->id, $package->received_by);
        $this->assertSame(PackageStatus::ReadyForPickup, $package->status);
        $this->assertNotSame('TIK-000000-9999', $package->tracking_code);
        $this->assertSame('3.75', $package->storage_price);
    }

    public function test_package_rejects_category_from_another_company(): void
    {
        [$user, , , $category] = $this->userWithMainBranch(UserRole::Operator);
        $otherCategory = PackageCategory::factory()->create();

        $response = $this->actingAs($user)->post(route('packages.store'), [
            ...$this->validPayload($category),
            'package_category_id' => $otherCategory->id,
        ]);

        $response->assertSessionHasErrors('package_category_id');
        $this->assertDatabaseCount('packages', 0);
    }

    public function test_package_keeps_registered_price_after_category_price_changes(): void
    {
        [$user, , , $category] = $this->userWithMainBranch(UserRole::Operator);

        $this->actingAs($user)->post(route('packages.store'), $this->validPayload($category));
        $category->update(['price' => '9.50']);

        $this->assertSame('3.75', Package::query()->sole()->storage_price);
    }

    public function test_package_cannot_use_the_main_branch_from_another_company(): void
    {
        $company = Company::factory()->create();
        $user = User::factory()->for($company)->withRole(UserRole::Operator)->create();
        $category = PackageCategory::factory()->for($company)->create();
        $otherCompany = Company::factory()->create();
        $otherBranch = Branch::factory()->for($otherCompany)->create([
            'name' => (string) config('tikshop.main_branch.name'),
        ]);

        $response = $this->actingAs($user)->post(route('packages.store'), [
            ...$this->validPayload($category),
            'branch_id' => $otherBranch->id,
        ]);

        $response->assertNotFound();
        $this->assertDatabaseCount('packages', 0);
    }

    /** @return array{User, Company, Branch, PackageCategory} */
    private function userWithMainBranch(UserRole $role): array
    {
        $company = Company::factory()->create();
        $mainBranch = Branch::factory()->for($company)->create([
            'name' => (string) config('tikshop.main_branch.name'),
        ]);
        $user = User::factory()->for($company)->withRole($role)->create();
        $category = PackageCategory::factory()->for($company)->create([
            'name' => 'Mediano',
            'code_prefix' => 'M',
            'code_start' => 1,
            'code_end' => 10,
            'price' => '3.75',
        ]);

        return [$user, $company, $mainBranch, $category];
    }

    /** @return array<string, string> */
    private function validPayload(PackageCategory $category): array
    {
        $seller = Seller::factory()->for($category->company)->create([
            'name' => 'María Pérez',
            'business_name' => null,
            'phone' => '70000001',
        ]);

        return [
            'seller_id' => (string) $seller->id,
            'package_category_id' => (string) $category->id,
            'storage_code' => ' s5-11 ',
            'sender_name' => 'María Pérez',
            'sender_phone' => '70000001',
            'recipient_name' => 'Juan López',
            'recipient_phone' => '70000002',
            'description' => 'Caja mediana',
            'notes' => 'Manipular con cuidado',
        ];
    }
}
