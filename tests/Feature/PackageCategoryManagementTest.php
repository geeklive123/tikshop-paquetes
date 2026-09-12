<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Company;
use App\Models\PackageCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class PackageCategoryManagementTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_owner_can_create_category_for_their_company(): void
    {
        $owner = User::factory()->withRole(UserRole::Owner)->create();

        $response = $this->actingAs($owner)->post(route('package-categories.store'), $this->validPayload());

        $category = PackageCategory::query()->sole();
        $response->assertRedirect(route('package-categories.edit', $category));
        $this->assertSame($owner->company_id, $category->company_id);
        $this->assertSame('3.00', $category->price);
    }

    public function test_admin_can_update_category(): void
    {
        $company = Company::factory()->create();
        $admin = User::factory()->for($company)->withRole(UserRole::Admin)->create();
        $category = PackageCategory::factory()->for($company)->create();

        $response = $this->actingAs($admin)->put(route('package-categories.update', $category), [
            ...$this->validPayload(),
            'name' => 'Mediano actualizado',
            'price' => '4.25',
        ]);

        $response->assertRedirect(route('package-categories.edit', $category));
        $this->assertSame('Mediano actualizado', $category->fresh()->name);
        $this->assertSame('4.25', $category->fresh()->price);
    }

    public function test_operator_cannot_manage_categories(): void
    {
        $operator = User::factory()->withRole(UserRole::Operator)->create();

        $response = $this->actingAs($operator)->get(route('package-categories.index'));

        $response->assertForbidden();
    }

    public function test_category_from_another_company_is_not_found(): void
    {
        $owner = User::factory()->withRole(UserRole::Owner)->create();
        $otherCategory = PackageCategory::factory()->create();

        $response = $this->actingAs($owner)->get(route('package-categories.edit', $otherCategory));

        $response->assertNotFound();
    }

    /** @return array<string, string> */
    private function validPayload(): array
    {
        return [
            'name' => 'Mediano',
            'code_prefix' => 'M',
            'code_start' => '1',
            'code_end' => '10',
            'price' => '3.00',
            'color' => '#F59E0B',
            'active' => '1',
        ];
    }
}
