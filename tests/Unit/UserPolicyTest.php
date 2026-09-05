<?php

namespace Tests\Unit;

use App\Enums\UserRole;
use App\Models\User;
use App\Policies\UserPolicy;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class UserPolicyTest extends TestCase
{
    /** @return array<string, array{UserRole, UserRole, bool}> */
    public static function managementMatrix(): array
    {
        return [
            'owner manages owner' => [UserRole::Owner, UserRole::Owner, true],
            'owner manages admin' => [UserRole::Owner, UserRole::Admin, true],
            'owner manages operator' => [UserRole::Owner, UserRole::Operator, true],
            'admin cannot manage owner' => [UserRole::Admin, UserRole::Owner, false],
            'admin manages admin' => [UserRole::Admin, UserRole::Admin, true],
            'admin manages operator' => [UserRole::Admin, UserRole::Operator, true],
            'operator cannot manage owner' => [UserRole::Operator, UserRole::Owner, false],
            'operator cannot manage admin' => [UserRole::Operator, UserRole::Admin, false],
            'operator cannot manage operator' => [UserRole::Operator, UserRole::Operator, false],
        ];
    }

    #[DataProvider('managementMatrix')]
    public function test_only_allowed_roles_can_manage_user_in_same_company(UserRole $actorRole, UserRole $targetRole, bool $allowed): void
    {
        $actor = new User(['company_id' => 1, 'role' => $actorRole]);
        $target = new User(['company_id' => 1, 'role' => $targetRole]);

        $response = (new UserPolicy)->update($actor, $target);

        $this->assertSame($allowed, $response->allowed());
    }

    public function test_cross_company_user_is_hidden(): void
    {
        $actor = new User(['company_id' => 1, 'role' => UserRole::Owner]);
        $target = new User(['company_id' => 2, 'role' => UserRole::Operator]);

        $response = (new UserPolicy)->view($actor, $target);

        $this->assertFalse($response->allowed());
        $this->assertSame(404, $response->status());
    }
}
