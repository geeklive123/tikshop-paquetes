<?php

namespace Tests\Unit;

use App\Enums\UserRole;
use App\Models\User;
use App\Policies\ReportPolicy;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class ReportPolicyTest extends TestCase
{
    /** @return array<string, array{UserRole, bool}> */
    public static function reportAccessMatrix(): array
    {
        return [
            'owner accesses' => [UserRole::Owner, true],
            'admin accesses' => [UserRole::Admin, true],
            'operator is forbidden' => [UserRole::Operator, false],
        ];
    }

    #[DataProvider('reportAccessMatrix')]
    public function test_only_owner_and_admin_with_company_can_access_reports(UserRole $role, bool $allowed): void
    {
        $user = new User(['company_id' => 1, 'role' => $role]);

        $this->assertSame($allowed, (new ReportPolicy)->viewAny($user));
    }

    public function test_user_without_company_cannot_access_reports(): void
    {
        $user = new User(['company_id' => null, 'role' => UserRole::Owner]);

        $this->assertFalse((new ReportPolicy)->viewAny($user));
    }
}
