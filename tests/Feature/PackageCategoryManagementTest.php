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
        $this->assertSame('0.70', $category->commission_rate);
        $this->assertSame('1.00', $category->weekly_storage_increment);
        $this->assertSame(3, $category->storage_increment_interval_weeks);
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
            'storage_increment_interval_weeks' => 2,
        ]);

        $response->assertRedirect(route('package-categories.edit', $category));
        $this->assertSame('Mediano actualizado', $category->fresh()->name);
        $this->assertSame('4.25', $category->fresh()->price);
        $this->assertSame(2, $category->fresh()->storage_increment_interval_weeks);
    }

    public function test_operator_cannot_manage_categories(): void
    {
        $operator = User::factory()->withRole(UserRole::Operator)->create();

        $response = $this->actingAs($operator)->get(route('package-categories.index'));

        $response->assertForbidden();
    }

    public function test_operator_cannot_modify_weekly_storage_increment(): void
    {
        $company = Company::factory()->create();
        $operator = User::factory()->for($company)->withRole(UserRole::Operator)->create();
        $category = PackageCategory::factory()->for($company)->create(['weekly_storage_increment' => '1.00']);

        $this->actingAs($operator)->put(route('package-categories.update', $category), [
            ...$this->validPayload(),
            'weekly_storage_increment' => '9.00',
            'storage_increment_interval_weeks' => 2,
        ])->assertForbidden();

        $this->assertSame('1.00', $category->fresh()->weekly_storage_increment);
        $this->assertSame(1, $category->fresh()->storage_increment_interval_weeks);
    }

    public function test_category_from_another_company_is_not_found(): void
    {
        $owner = User::factory()->withRole(UserRole::Owner)->create();
        $otherCategory = PackageCategory::factory()->create();

        $response = $this->actingAs($owner)->get(route('package-categories.edit', $otherCategory));

        $response->assertNotFound();
    }

    public function test_interval_is_required_and_must_be_between_one_and_52(): void
    {
        $owner = User::factory()->withRole(UserRole::Owner)->create();

        foreach ([null, 0, 53, '1.5'] as $value) {
            $this->actingAs($owner)->post(route('package-categories.store'), [
                ...$this->validPayload(),
                'storage_increment_interval_weeks' => $value,
            ])->assertSessionHasErrors('storage_increment_interval_weeks');
        }

        $this->assertDatabaseCount('package_categories', 0);
    }

    public function test_interval_defaults_to_one_for_existing_records(): void
    {
        $category = PackageCategory::factory()->make();
        unset($category->storage_increment_interval_weeks);
        $category->save();

        $this->assertSame(1, $category->fresh()->storage_increment_interval_weeks);
    }

    public function test_category_form_shows_interval_and_pricing_fields(): void
    {
        $owner = User::factory()->withRole(UserRole::Owner)->create();
        $category = PackageCategory::factory()->for($owner->company)->create(['storage_increment_interval_weeks' => 3]);

        $this->actingAs($owner)->get(route('package-categories.edit', $category))
            ->assertOk()->assertSee('Precio base')->assertSee('Incremento de almacenaje')
            ->assertSee('Aplicar incremento cada')->assertSee('name="storage_increment_interval_weeks"', false)
            ->assertSee('value="3"', false)->assertSee('Comisión vendedor');
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
            'commission_rate' => '0.70',
            'weekly_storage_increment' => '1.00',
            'storage_increment_interval_weeks' => '3',
            'color' => '#F59E0B',
            'active' => '1',
        ];
    }
}
