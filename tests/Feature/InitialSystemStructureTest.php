<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Branch;
use App\Models\Company;
use App\Models\User;
use Database\Seeders\InitialSystemSeeder;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class InitialSystemStructureTest extends TestCase
{
    use LazilyRefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('tikshop.initial_owner.password', 'configured-test-password');
    }

    public function test_initial_company_is_created_with_a_public_ulid(): void
    {
        $this->seed(InitialSystemSeeder::class);

        $company = Company::query()->where('name', 'Tik Shop')->firstOrFail();

        $this->assertModelExists($company);
        $this->assertTrue($company->active);
        $this->assertTrue(Str::isUlid($company->ulid));
        $this->assertSame('ulid', $company->getRouteKeyName());
    }

    public function test_main_branch_is_created_for_the_initial_company(): void
    {
        $this->seed(InitialSystemSeeder::class);

        $company = Company::query()->where('name', 'Tik Shop')->firstOrFail();
        $branch = Branch::query()->where('name', 'Tik Shop - Principal')->firstOrFail();

        $this->assertTrue($branch->company->is($company));
        $this->assertTrue($company->branches->contains($branch));
        $this->assertTrue($branch->active);
        $this->assertTrue(Str::isUlid($branch->ulid));
        $this->assertSame('ulid', $branch->getRouteKeyName());
    }

    public function test_initial_owner_belongs_to_the_initial_company(): void
    {
        $this->seed(InitialSystemSeeder::class);

        $company = Company::query()->where('name', 'Tik Shop')->firstOrFail();
        $owner = User::query()->where('email', 'admin@tikshop.local')->firstOrFail();

        $this->assertTrue($owner->company->is($company));
        $this->assertTrue($company->users->contains($owner));
        $this->assertSame('Super Administrador Tik Shop', $owner->name);
        $this->assertSame(UserRole::Owner, $owner->role);
    }

    public function test_initial_owner_is_not_overwritten_when_seeder_runs_again(): void
    {
        $company = Company::factory()->create(['name' => 'Tik Shop']);
        $existingOwner = User::factory()->for($company)->create([
            'name' => 'Owner existente',
            'email' => 'admin@tikshop.local',
            'password' => 'existing-password',
            'role' => UserRole::Owner,
            'active' => false,
        ]);
        $otherUser = User::factory()->create();

        $this->seed(InitialSystemSeeder::class);
        $this->seed(InitialSystemSeeder::class);

        $existingOwner->refresh();
        $this->assertSame('Owner existente', $existingOwner->name);
        $this->assertSame(UserRole::Owner, $existingOwner->role);
        $this->assertSame('Tik Shop', $existingOwner->company->name);
        $this->assertFalse($existingOwner->active);
        $this->assertTrue(Hash::check('existing-password', $existingOwner->password));
        $this->assertSame(1, User::query()->where('email', 'admin@tikshop.local')->count());
        $this->assertModelExists($otherUser);
    }

    public function test_initial_seeder_fails_without_an_owner_password(): void
    {
        config()->set('tikshop.initial_owner.password');

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Configure a secure TIKSHOP_OWNER_PASSWORD before seeding.');

        $this->seed(InitialSystemSeeder::class);
    }

    /**
     * @return array<string, array{UserRole}>
     */
    public static function allowedRoles(): array
    {
        return [
            'owner' => [UserRole::Owner],
            'admin' => [UserRole::Admin],
            'operator' => [UserRole::Operator],
        ];
    }

    #[DataProvider('allowedRoles')]
    public function test_allowed_roles_are_stored_and_cast_to_the_enum(UserRole $role): void
    {
        $user = User::factory()->withRole($role)->create();

        $this->assertSame($role, $user->fresh()->role);

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'role' => $role->value,
        ]);
    }
}
