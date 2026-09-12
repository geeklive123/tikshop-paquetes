<?php

namespace Tests\Unit;

use App\Enums\UserRole;
use App\Models\Seller;
use App\Models\User;
use App\Policies\SellerPolicy;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class SellerPolicyTest extends TestCase
{
    /** @return array<string, array{UserRole, bool}> */
    public static function sellerManagementMatrix(): array
    {
        return [
            'owner manages' => [UserRole::Owner, true],
            'admin manages' => [UserRole::Admin, true],
            'operator cannot manage' => [UserRole::Operator, false],
        ];
    }

    #[DataProvider('sellerManagementMatrix')]
    public function test_only_owner_and_admin_can_update_or_toggle_seller(UserRole $role, bool $allowed): void
    {
        $user = new User(['company_id' => 1, 'role' => $role]);
        $seller = new Seller(['company_id' => 1]);
        $policy = new SellerPolicy;

        $this->assertSame($allowed, $policy->update($user, $seller)->allowed());
        $this->assertSame($allowed, $policy->toggleStatus($user, $seller)->allowed());
    }

    public function test_all_roles_can_view_and_create_sellers(): void
    {
        $policy = new SellerPolicy;

        foreach (UserRole::cases() as $role) {
            $user = new User(['company_id' => 1, 'role' => $role]);

            $this->assertTrue($policy->viewAny($user));
            $this->assertTrue($policy->create($user));
        }
    }

    public function test_cross_company_seller_is_hidden(): void
    {
        $user = new User(['company_id' => 1, 'role' => UserRole::Owner]);
        $seller = new Seller(['company_id' => 2]);

        $response = (new SellerPolicy)->view($user, $seller);

        $this->assertFalse($response->allowed());
        $this->assertSame(404, $response->status());
    }
}
