<?php

namespace Tests\Feature;

use App\Enums\PackageStatus;
use App\Models\Branch;
use App\Models\Company;
use App\Models\Package;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_dashboard_requires_authentication(): void
    {
        $response = $this->get(route('dashboard'));

        $response->assertRedirect(route('login'));
    }

    public function test_dashboard_is_accessible_to_an_authenticated_user(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertOk();
        $response->assertSee('Resumen operativo');
        $response->assertSee('Últimos paquetes registrados');
    }

    public function test_dashboard_metrics_and_recent_packages_are_isolated_by_company(): void
    {
        $this->travelTo('2026-09-04 14:00:00');
        [$user, $branch] = $this->userWithBranch();
        [, $otherBranch] = $this->userWithBranch();

        $ownReceived = Package::factory()->forBranch($branch)->create([
            'tracking_code' => 'TIK-260904-0001',
            'status' => PackageStatus::Received,
            'received_at' => now(),
        ]);
        Package::factory()->forBranch($branch)->create([
            'tracking_code' => 'TIK-260904-0002',
            'status' => PackageStatus::ReadyForPickup,
            'received_at' => now(),
        ]);
        Package::factory()->forBranch($branch)->create([
            'tracking_code' => 'TIK-260904-0003',
            'status' => PackageStatus::Delivered,
            'received_at' => now()->subDay(),
            'delivered_at' => now(),
        ]);
        $otherPackage = Package::factory()->forBranch($otherBranch)->create([
            'tracking_code' => 'TIK-260904-9999',
            'status' => PackageStatus::Received,
            'received_at' => now(),
        ]);

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertOk();
        $response->assertViewHas('metrics', [
            'received_today' => 2,
            'pending' => 1,
            'ready_for_pickup' => 1,
            'delivered_today' => 1,
        ]);
        $response->assertSee($ownReceived->tracking_code);
        $response->assertDontSee($otherPackage->tracking_code);
    }

    /** @return array{User, Branch} */
    private function userWithBranch(): array
    {
        $company = Company::factory()->create();
        $branch = Branch::factory()->for($company)->create();
        $user = User::factory()->for($company)->create();

        return [$user, $branch];
    }
}
