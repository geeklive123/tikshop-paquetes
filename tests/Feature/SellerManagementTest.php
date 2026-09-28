<?php

namespace Tests\Feature;

use App\Enums\PackageStatus;
use App\Enums\UserRole;
use App\Models\Branch;
use App\Models\Company;
use App\Models\Package;
use App\Models\Seller;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class SellerManagementTest extends TestCase
{
    use LazilyRefreshDatabase;

    /** @return array<string, array{UserRole}> */
    public static function rolesThatCanCreateSellers(): array
    {
        return ['owner' => [UserRole::Owner], 'admin' => [UserRole::Admin], 'operator' => [UserRole::Operator]];
    }

    #[DataProvider('rolesThatCanCreateSellers')]
    public function test_authorized_role_creates_seller_for_own_company(UserRole $role): void
    {
        $user = User::factory()->withRole($role)->create();

        $response = $this->actingAs($user)->post(route('sellers.store'), $this->validPayload());

        $seller = Seller::query()->sole();
        $response->assertRedirect(route('sellers.show', $seller));
        $this->assertSame($user->company_id, $seller->company_id);
        $this->assertSame($user->company->id, $seller->company->id);
        $this->assertTrue($seller->active);
    }

    public function test_operator_cannot_deactivate_seller(): void
    {
        $company = Company::factory()->create();
        $operator = User::factory()->for($company)->withRole(UserRole::Operator)->create();
        $seller = Seller::factory()->for($company)->create();

        $response = $this->actingAs($operator)->patch(route('sellers.status.update', $seller));

        $response->assertForbidden();
        $this->assertTrue($seller->fresh()->active);
    }

    public function test_operator_cannot_create_inactive_seller_by_manipulating_payload(): void
    {
        $operator = User::factory()->withRole(UserRole::Operator)->create();

        $this->actingAs($operator)->post(route('sellers.store'), [...$this->validPayload(), 'active' => '0']);

        $this->assertTrue(Seller::query()->sole()->active);
    }

    public function test_operator_can_create_seller_and_return_to_package_registration(): void
    {
        $operator = User::factory()->withRole(UserRole::Operator)->create();

        $response = $this->actingAs($operator)->post(route('sellers.store'), [
            ...$this->validPayload(),
            'return_to' => 'packages.create',
        ]);

        $seller = Seller::query()->sole();
        $response->assertRedirect(route('packages.create', ['seller' => $seller->ulid]));
    }

    public function test_seller_requires_name_phone_and_boolean_status(): void
    {
        $owner = User::factory()->withRole(UserRole::Owner)->create();

        $response = $this->actingAs($owner)->post(route('sellers.store'), ['active' => 'invalid']);

        $response->assertSessionHasErrors(['name', 'phone', 'active']);
        $this->assertDatabaseCount('sellers', 0);
    }

    /** @return array<string, array{string, mixed}> */
    public static function invalidSellerFields(): array
    {
        return [
            'name maximum' => ['name', str_repeat('a', 151)],
            'business name maximum' => ['business_name', str_repeat('a', 151)],
            'phone maximum' => ['phone', str_repeat('1', 31)],
            'document type allowed values' => ['document_type', 'passport'],
            'document number maximum' => ['document_number', str_repeat('1', 51)],
            'address maximum' => ['address', str_repeat('a', 256)],
            'notes maximum' => ['notes', str_repeat('a', 2001)],
        ];
    }

    #[DataProvider('invalidSellerFields')]
    public function test_seller_rejects_invalid_field_value(string $field, mixed $value): void
    {
        $owner = User::factory()->withRole(UserRole::Owner)->create();

        $response = $this->actingAs($owner)->post(route('sellers.store'), [...$this->validPayload(), $field => $value]);

        $response->assertSessionHasErrors($field);
        $this->assertDatabaseCount('sellers', 0);
    }

    public function test_user_does_not_see_sellers_from_another_company(): void
    {
        $user = User::factory()->withRole(UserRole::Operator)->create();
        $ownSeller = Seller::factory()->for($user->company)->create(['name' => 'Vendedor visible']);
        $otherSeller = Seller::factory()->create(['name' => 'Vendedor secreto']);

        $response = $this->actingAs($user)->get(route('sellers.index'));

        $response->assertSee($ownSeller->name);
        $response->assertDontSee($otherSeller->name);
        $this->actingAs($user)->get(route('sellers.show', $otherSeller))->assertNotFound();
    }

    public function test_owner_updates_and_toggles_seller_without_deleting_it(): void
    {
        $company = Company::factory()->create();
        $owner = User::factory()->for($company)->withRole(UserRole::Owner)->create();
        $seller = Seller::factory()->for($company)->create();

        $updateResponse = $this->actingAs($owner)->put(route('sellers.update', $seller), [...$this->validPayload(), 'name' => 'Nombre actualizado']);

        $updateResponse->assertRedirect(route('sellers.show', $seller));
        $this->assertSame('Nombre actualizado', $seller->fresh()->name);

        $statusResponse = $this->actingAs($owner)->patch(route('sellers.status.update', $seller));

        $statusResponse->assertRedirect(route('sellers.show', $seller));
        $this->assertFalse($seller->fresh()->active);
        $this->assertModelExists($seller);
    }

    public function test_seller_detail_shows_only_its_packages_and_correct_summary(): void
    {
        $company = Company::factory()->create();
        $owner = User::factory()->for($company)->withRole(UserRole::Owner)->create();
        $seller = Seller::factory()->for($company)->create();
        $otherSeller = Seller::factory()->for($company)->create();
        Package::factory()->for($company)->for($seller)->withStatus(PackageStatus::Received)->create(['tracking_code' => 'TIK-260912-0001', 'recipient_name' => 'Destinatario visible', 'storage_price' => '2.50']);
        Package::factory()->for($company)->for($seller)->withStatus(PackageStatus::ReadyForPickup)->create(['tracking_code' => 'TIK-260912-0002', 'storage_price' => '3.50']);
        Package::factory()->for($company)->for($seller)->withStatus(PackageStatus::Delivered)->create(['tracking_code' => 'TIK-260912-0003', 'storage_price' => '4.00']);
        Package::factory()->for($company)->for($seller)->withStatus(PackageStatus::Cancelled)->create(['tracking_code' => 'TIK-260912-0004', 'storage_price' => '5.00']);
        Package::factory()->for($company)->for($otherSeller)->create(['tracking_code' => 'TIK-260912-0005', 'recipient_name' => 'Destinatario oculto', 'storage_price' => '100.00']);
        $otherCompanyPackage = Package::factory()->forSeller(Seller::factory()->create())->create([
            'tracking_code' => 'TIK-260912-0006',
            'recipient_name' => 'Otra empresa oculta',
            'storage_price' => '200.00',
        ]);

        $response = $this->actingAs($owner)->get(route('sellers.show', $seller));

        $response->assertSee('TIK-260912-0001');
        $response->assertSee('TIK-260912-0002');
        $response->assertSee('TIK-260912-0003');
        $response->assertSee('TIK-260912-0004');
        $response->assertSee('Destinatario visible');
        $response->assertDontSee('TIK-260912-0005');
        $response->assertDontSee($otherCompanyPackage->tracking_code);
        $response->assertDontSee('Destinatario oculto');
        $response->assertDontSee('Otra empresa oculta');
        $response->assertSee('Bs 15.00');
        $response->assertSee('No representa una comisión calculada.');
        $response->assertViewHas('metrics', fn (object $metrics): bool => (int) $metrics->total_packages === 4
            && (int) $metrics->pending_packages === 2
            && (int) $metrics->delivered_packages === 1
            && (int) $metrics->cancelled_packages === 1
            && (float) $metrics->storage_amount === 15.0);
    }

    public function test_owner_sees_seller_packages_paginated(): void
    {
        $company = Company::factory()->create();
        $owner = User::factory()->for($company)->withRole(UserRole::Owner)->create();
        $seller = Seller::factory()->for($company)->create();
        Package::factory()->count(16)->for($company)->for($seller)->create();

        $response = $this->actingAs($owner)->get(route('sellers.show', $seller));

        $response->assertViewHas('packages', fn ($packages): bool => $packages->count() === 15
            && $packages->total() === 16
            && $packages->hasMorePages());
        $response->assertSee('Todos los paquetes');
    }

    public function test_seller_package_filters_apply_status_dates_and_search(): void
    {
        $company = Company::factory()->create();
        $admin = User::factory()->for($company)->withRole(UserRole::Admin)->create();
        $seller = Seller::factory()->for($company)->create();
        Package::factory()->for($company)->for($seller)->withStatus(PackageStatus::Delivered)->create([
            'tracking_code' => 'TIK-FILTRO-001',
            'recipient_name' => 'Ana Visible',
            'received_at' => '2026-09-10 10:00:00',
        ]);
        Package::factory()->for($company)->for($seller)->withStatus(PackageStatus::Delivered)->create([
            'tracking_code' => 'TIK-FILTRO-002',
            'recipient_name' => 'Ana Fuera de Fecha',
            'received_at' => '2026-08-10 10:00:00',
        ]);
        Package::factory()->for($company)->for($seller)->withStatus(PackageStatus::Received)->create([
            'tracking_code' => 'TIK-FILTRO-003',
            'recipient_name' => 'Ana Estado Distinto',
            'received_at' => '2026-09-10 10:00:00',
        ]);
        Package::factory()->for($company)->for($seller)->withStatus(PackageStatus::Delivered)->create([
            'tracking_code' => 'TIK-FILTRO-004',
            'recipient_name' => 'Otra Persona',
            'received_at' => '2026-09-10 10:00:00',
        ]);

        $response = $this->actingAs($admin)->get(route('sellers.show', [
            'seller' => $seller,
            'status' => PackageStatus::Delivered->value,
            'date_from' => '2026-09-01',
            'date_to' => '2026-09-30',
            'search' => 'Ana',
        ]));

        $response->assertSee('TIK-FILTRO-001');
        $response->assertDontSee('TIK-FILTRO-002');
        $response->assertDontSee('TIK-FILTRO-003');
        $response->assertDontSee('TIK-FILTRO-004');
        $response->assertViewHas('packages', fn ($packages): bool => $packages->total() === 1);
    }

    public function test_operator_sees_more_than_ten_seller_packages_with_the_full_paginated_table(): void
    {
        $company = Company::factory()->create();
        $operator = User::factory()->for($company)->withRole(UserRole::Operator)->create();
        $seller = Seller::factory()->for($company)->create();
        Package::factory()->count(16)->for($company)->for($seller)->create();

        $response = $this->actingAs($operator)->get(route('sellers.show', $seller));

        $response->assertViewHas('packages', fn ($packages): bool => $packages->count() === 15
            && $packages->total() === 16
            && $packages->hasMorePages());
        $response->assertSee('Todos los paquetes');
        $response->assertSee('Monto total asociado a paquetes');
        $response->assertSee('Fecha desde');
    }

    public function test_operator_can_navigate_to_the_second_seller_package_page(): void
    {
        $company = Company::factory()->create();
        $operator = User::factory()->for($company)->withRole(UserRole::Operator)->create();
        $seller = Seller::factory()->for($company)->create();
        Package::factory()->count(16)->for($company)->for($seller)->create();

        $response = $this->actingAs($operator)->get(route('sellers.show', ['seller' => $seller, 'page' => 2]));

        $response->assertViewHas('packages', fn ($packages): bool => $packages->currentPage() === 2
            && $packages->count() === 1
            && $packages->total() === 16);
    }

    public function test_operator_does_not_see_seller_package_row_from_another_company(): void
    {
        $company = Company::factory()->create();
        $operator = User::factory()->for($company)->withRole(UserRole::Operator)->create();
        $seller = Seller::factory()->for($company)->create();
        Package::factory()->for($company)->for($seller)->create([
            'tracking_code' => 'TIK-PROPIO-001',
        ]);
        $otherCompany = Company::factory()->create();
        $otherBranch = Branch::factory()->for($otherCompany)->create();
        Package::factory()->forBranch($otherBranch)->create([
            'seller_id' => $seller->id,
            'tracking_code' => 'TIK-OTRA-CIA01',
        ]);

        $response = $this->actingAs($operator)->get(route('sellers.show', $seller));

        $response->assertSee('TIK-PROPIO-001');
        $response->assertDontSee('TIK-OTRA-CIA01');
    }

    /** @return array<string, string> */
    private function validPayload(): array
    {
        return [
            'name' => 'María Pérez', 'business_name' => 'Comercial MP', 'phone' => '70000001',
            'document_type' => 'ci', 'document_number' => '1234567', 'address' => 'Av. Principal 123',
            'notes' => 'Vendedora frecuente', 'active' => '1',
        ];
    }
}
