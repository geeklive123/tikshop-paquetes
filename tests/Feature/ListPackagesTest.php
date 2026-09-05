<?php

namespace Tests\Feature;

use App\Enums\PackageStatus;
use App\Models\Branch;
use App\Models\Company;
use App\Models\Package;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class ListPackagesTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_search_finds_package_by_tracking_code(): void
    {
        [$user, $branch] = $this->userWithBranch();
        $matchingPackage = Package::factory()->forBranch($branch)->create(['tracking_code' => 'TIK-260904-0042']);
        $otherPackage = Package::factory()->forBranch($branch)->create(['tracking_code' => 'TIK-260904-0043']);

        $response = $this->actingAs($user)->get(route('packages.index', ['search' => '0042']));

        $response->assertOk();
        $response->assertSee($matchingPackage->tracking_code);
        $response->assertDontSee($otherPackage->tracking_code);
    }

    public function test_search_finds_package_by_recipient(): void
    {
        [$user, $branch] = $this->userWithBranch();
        $matchingPackage = Package::factory()->forBranch($branch)->create(['recipient_name' => 'Destinataria Especial']);
        $otherPackage = Package::factory()->forBranch($branch)->create(['recipient_name' => 'Otra Persona']);

        $response = $this->actingAs($user)->get(route('packages.index', ['search' => 'Destinataria']));

        $response->assertOk();
        $response->assertSee($matchingPackage->recipient_name);
        $response->assertDontSee($otherPackage->recipient_name);
    }

    public function test_status_filter_only_lists_matching_packages(): void
    {
        [$user, $branch] = $this->userWithBranch();
        $receivedPackage = Package::factory()->forBranch($branch)->create();
        $deliveredPackage = Package::factory()->forBranch($branch)->withStatus(PackageStatus::Delivered)->create();

        $response = $this->actingAs($user)->get(route('packages.index', ['status' => PackageStatus::Delivered->value]));

        $response->assertOk();
        $response->assertSee($deliveredPackage->tracking_code);
        $response->assertDontSee($receivedPackage->tracking_code);
    }

    public function test_list_only_contains_packages_from_the_users_company(): void
    {
        [$user, $branch] = $this->userWithBranch();
        $ownPackage = Package::factory()->forBranch($branch)->create();
        [, $otherBranch] = $this->userWithBranch();
        $otherPackage = Package::factory()->forBranch($otherBranch)->create();

        $response = $this->actingAs($user)->get(route('packages.index'));

        $response->assertOk();
        $response->assertSee($ownPackage->tracking_code);
        $response->assertDontSee($otherPackage->tracking_code);
    }

    /** @return array{User, Branch} */
    private function userWithBranch(): array
    {
        $company = Company::factory()->create();
        $branch = Branch::factory()->for($company)->create([
            'name' => (string) config('tikshop.main_branch.name'),
        ]);
        $user = User::factory()->for($company)->create();

        return [$user, $branch];
    }
}
