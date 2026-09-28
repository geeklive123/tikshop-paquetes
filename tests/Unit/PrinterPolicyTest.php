<?php

namespace Tests\Unit;

use App\Enums\UserRole;
use App\Models\Printer;
use App\Models\User;
use App\Policies\PrinterPolicy;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class PrinterPolicyTest extends TestCase
{
    /** @return array<string, array{UserRole, bool}> */
    public static function managementMatrix(): array
    {
        return [
            'owner manages printers' => [UserRole::Owner, true],
            'admin manages printers' => [UserRole::Admin, true],
            'operator cannot manage printers' => [UserRole::Operator, false],
        ];
    }

    #[DataProvider('managementMatrix')]
    public function test_only_owner_and_admin_can_manage_printers(UserRole $role, bool $allowed): void
    {
        $user = new User(['company_id' => 1, 'role' => $role]);
        $printer = new Printer(['company_id' => 1]);
        $policy = new PrinterPolicy;

        $this->assertSame($allowed, $policy->create($user));
        $this->assertSame($allowed, $policy->update($user, $printer)->allowed());
    }

    public function test_operator_can_view_printer_in_their_company(): void
    {
        $operator = new User(['company_id' => 1, 'role' => UserRole::Operator]);
        $printer = new Printer(['company_id' => 1]);
        $policy = new PrinterPolicy;

        $this->assertTrue($policy->viewAny($operator));
        $this->assertTrue($policy->view($operator, $printer)->allowed());
    }

    public function test_cross_company_printer_is_hidden(): void
    {
        $owner = new User(['company_id' => 1, 'role' => UserRole::Owner]);
        $printer = new Printer(['company_id' => 2]);

        $response = (new PrinterPolicy)->view($owner, $printer);

        $this->assertFalse($response->allowed());
        $this->assertSame(404, $response->status());
    }
}
