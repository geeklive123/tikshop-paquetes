<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Branch;
use App\Models\Company;
use App\Models\Package;
use App\Models\PackageCategory;
use App\Models\Seller;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class SellerPackageIntegrationTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_active_seller_can_be_assigned_and_contact_is_snapshotted(): void
    {
        [$operator, $category, $seller] = $this->packageContext();

        $this->actingAs($operator)->post(route('packages.store'), [
            ...$this->validPackagePayload($category, $seller),
            'sender_name' => 'Dato manipulado',
            'sender_phone' => '00000000',
        ]);

        $package = Package::query()->sole();
        $this->assertSame($seller->id, $package->seller_id);
        $this->assertSame('Negocio Snapshot', $package->sender_name);
        $this->assertSame('70000009', $package->sender_phone);
        $this->assertTrue($package->seller->is($seller));
    }

    public function test_inactive_seller_cannot_be_assigned(): void
    {
        [$operator, $category, $seller] = $this->packageContext(activeSeller: false);

        $response = $this->actingAs($operator)->post(route('packages.store'), $this->validPackagePayload($category, $seller));

        $response->assertSessionHasErrors('seller_id');
        $this->assertDatabaseCount('packages', 0);
    }

    public function test_seller_from_another_company_cannot_be_assigned(): void
    {
        [$operator, $category] = $this->packageContext();
        $otherSeller = Seller::factory()->create();

        $response = $this->actingAs($operator)->post(route('packages.store'), $this->validPackagePayload($category, $otherSeller));

        $response->assertSessionHasErrors('seller_id');
        $this->assertDatabaseCount('packages', 0);
    }

    public function test_editing_seller_does_not_change_package_historical_contact(): void
    {
        [$operator, $category, $seller] = $this->packageContext();
        $this->actingAs($operator)->post(route('packages.store'), $this->validPackagePayload($category, $seller));

        $seller->update(['business_name' => 'Negocio nuevo', 'phone' => '71111111']);

        $package = Package::query()->sole();
        $this->assertSame('Negocio Snapshot', $package->sender_name);
        $this->assertSame('70000009', $package->sender_phone);
    }

    /** @return array{User, PackageCategory, Seller} */
    private function packageContext(bool $activeSeller = true): array
    {
        $company = Company::factory()->create();
        Branch::factory()->for($company)->create(['name' => (string) config('tikshop.main_branch.name')]);
        $operator = User::factory()->for($company)->withRole(UserRole::Operator)->create();
        $category = PackageCategory::factory()->for($company)->create();
        $seller = Seller::factory()->for($company)->create([
            'name' => 'María Vendedora',
            'business_name' => 'Negocio Snapshot',
            'phone' => '70000009',
            'active' => $activeSeller,
        ]);

        return [$operator, $category, $seller];
    }

    /** @return array<string, string> */
    private function validPackagePayload(PackageCategory $category, Seller $seller): array
    {
        return [
            'seller_id' => (string) $seller->id,
            'package_category_id' => (string) $category->id,
            'storage_code' => 'A-10',
            'recipient_name' => 'Juan López',
            'recipient_phone' => '70000010',
            'description' => 'Caja',
            'notes' => 'Sin observaciones',
        ];
    }
}
