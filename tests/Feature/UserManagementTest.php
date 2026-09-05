<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Hash;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class UserManagementTest extends TestCase
{
    use LazilyRefreshDatabase;

    /** @return array<string, array{UserRole}> */
    public static function rolesCreatedByOwner(): array
    {
        return [
            'admin' => [UserRole::Admin],
            'operator' => [UserRole::Operator],
        ];
    }

    #[DataProvider('rolesCreatedByOwner')]
    public function test_owner_can_create_admin_and_operator(UserRole $role): void
    {
        $owner = User::factory()->withRole(UserRole::Owner)->create();

        $response = $this->actingAs($owner)->post(route('users.store'), $this->validPayload($role));

        $createdUser = User::query()->where('email', 'nuevo@tikshop.local')->sole();
        $response->assertRedirect(route('users.edit', $createdUser));
        $this->assertSame($owner->company_id, $createdUser->company_id);
        $this->assertSame($role, $createdUser->role);
    }

    public function test_admin_can_create_operator(): void
    {
        $admin = User::factory()->withRole(UserRole::Admin)->create();

        $response = $this->actingAs($admin)->post(route('users.store'), $this->validPayload(UserRole::Operator));

        $createdUser = User::query()->where('email', 'nuevo@tikshop.local')->sole();
        $response->assertRedirect(route('users.edit', $createdUser));
        $this->assertSame(UserRole::Operator, $createdUser->role);
    }

    public function test_admin_cannot_create_owner(): void
    {
        $admin = User::factory()->withRole(UserRole::Admin)->create();

        $response = $this->actingAs($admin)->post(route('users.store'), $this->validPayload(UserRole::Owner));

        $response->assertSessionHasErrors('role');
        $this->assertDatabaseMissing('users', ['email' => 'nuevo@tikshop.local']);
    }

    public function test_operator_cannot_access_user_management(): void
    {
        $operator = User::factory()->withRole(UserRole::Operator)->create();

        $response = $this->actingAs($operator)->get(route('users.index'));

        $response->assertForbidden();
    }

    public function test_user_from_another_company_cannot_be_viewed_for_editing(): void
    {
        $owner = User::factory()->withRole(UserRole::Owner)->create();
        $otherUser = User::factory()->create();

        $response = $this->actingAs($owner)->get(route('users.edit', $otherUser));

        $response->assertNotFound();
    }

    public function test_email_must_be_unique(): void
    {
        $owner = User::factory()->withRole(UserRole::Owner)->create();
        $existingUser = User::factory()->create();

        $response = $this->actingAs($owner)->post(route('users.store'), [
            ...$this->validPayload(UserRole::Operator),
            'email' => $existingUser->email,
        ]);

        $response->assertSessionHasErrors('email');
    }

    public function test_password_is_stored_hashed(): void
    {
        $owner = User::factory()->withRole(UserRole::Owner)->create();

        $this->actingAs($owner)->post(route('users.store'), $this->validPayload(UserRole::Operator));

        $createdUser = User::query()->where('email', 'nuevo@tikshop.local')->sole();
        $this->assertNotSame('secure-password', $createdUser->password);
        $this->assertTrue(Hash::check('secure-password', $createdUser->password));
    }

    public function test_last_active_owner_cannot_be_deactivated(): void
    {
        $owner = User::factory()->withRole(UserRole::Owner)->create();

        $response = $this->actingAs($owner)->patch(route('users.status.update', $owner));

        $response->assertSessionHasErrors('active');
        $this->assertTrue($owner->fresh()->active);
    }

    public function test_last_active_owner_cannot_remove_their_own_owner_role(): void
    {
        $owner = User::factory()->withRole(UserRole::Owner)->create();

        $response = $this->actingAs($owner)->put(route('users.update', $owner), [
            ...$this->validPayload(UserRole::Admin),
            'name' => $owner->name,
            'email' => $owner->email,
            'password' => null,
            'password_confirmation' => null,
        ]);

        $response->assertSessionHasErrors('active');
        $this->assertSame(UserRole::Owner, $owner->fresh()->role);
    }

    public function test_admin_cannot_modify_owner(): void
    {
        $company = Company::factory()->create();
        $admin = User::factory()->for($company)->withRole(UserRole::Admin)->create();
        $owner = User::factory()->for($company)->withRole(UserRole::Owner)->create();

        $response = $this->actingAs($admin)->get(route('users.edit', $owner));

        $response->assertForbidden();
    }

    public function test_owner_can_deactivate_and_activate_operator(): void
    {
        $company = Company::factory()->create();
        $owner = User::factory()->for($company)->withRole(UserRole::Owner)->create();
        $operator = User::factory()->for($company)->withRole(UserRole::Operator)->create();

        $deactivationResponse = $this->actingAs($owner)->patch(route('users.status.update', $operator));

        $deactivationResponse->assertSessionHas('status', 'Usuario desactivado correctamente.');
        $this->assertFalse($operator->fresh()->active);

        $response = $this->actingAs($owner)->patch(route('users.status.update', $operator));

        $response->assertRedirect(route('users.index'));
        $response->assertSessionHas('status', 'Usuario activado correctamente.');
        $this->assertTrue($operator->fresh()->active);
    }

    /** @return array<string, string|bool> */
    private function validPayload(UserRole $role): array
    {
        return [
            'name' => 'Nuevo Usuario',
            'email' => 'nuevo@tikshop.local',
            'role' => $role->value,
            'active' => true,
            'password' => 'secure-password',
            'password_confirmation' => 'secure-password',
        ];
    }
}
