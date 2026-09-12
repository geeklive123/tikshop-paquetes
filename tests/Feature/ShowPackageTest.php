<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Company;
use App\Models\Package;
use App\Models\PackageCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class ShowPackageTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_user_can_view_package_details_from_their_company_using_ulid(): void
    {
        [$user, $branch] = $this->userWithBranch();
        $category = PackageCategory::factory()->for($branch->company)->create(['name' => 'Grande']);
        $package = Package::factory()->forBranch($branch)->create([
            'package_category_id' => $category->id,
            'storage_code' => 'G4-02',
            'storage_price' => '5.00',
            'sender_name' => 'Remitente Prueba',
            'recipient_name' => 'Destinatario Prueba',
            'description' => 'Caja de documentos',
            'notes' => 'Sin doblar',
            'received_by' => $user->id,
        ]);

        $response = $this->actingAs($user)->get(route('packages.show', $package));

        $response->assertOk();
        $response->assertSee($package->tracking_code);
        $response->assertSee('Remitente Prueba');
        $response->assertSee('Destinatario Prueba');
        $response->assertSee('Caja de documentos');
        $response->assertSee('Sin doblar');
        $response->assertSee($user->name);
        $response->assertSee($branch->name);
        $response->assertSee('Grande');
        $response->assertSee('G4-02');
        $response->assertSee('Bs 5.00');
        $this->assertStringContainsString($package->ulid, route('packages.show', $package));
    }

    public function test_user_cannot_view_package_from_another_company(): void
    {
        [$user] = $this->userWithBranch();
        [, $otherBranch] = $this->userWithBranch();
        $otherPackage = Package::factory()->forBranch($otherBranch)->create();

        $response = $this->actingAs($user)->get(route('packages.show', $otherPackage));

        $response->assertNotFound();
    }

    public function test_package_detail_escapes_user_provided_content(): void
    {
        [$user, $branch] = $this->userWithBranch();
        $dangerousContent = '<script>alert("xss")</script>';
        $package = Package::factory()->forBranch($branch)->create([
            'sender_name' => $dangerousContent,
            'description' => $dangerousContent,
            'notes' => $dangerousContent,
        ]);

        $response = $this->actingAs($user)->get(route('packages.show', $package));

        $response->assertOk();
        $response->assertSee($dangerousContent);
        $response->assertDontSee($dangerousContent, escape: false);
    }

    public function test_user_cannot_view_success_screen_for_package_from_another_company(): void
    {
        [$user] = $this->userWithBranch();
        [, $otherBranch] = $this->userWithBranch();
        $otherPackage = Package::factory()->forBranch($otherBranch)->create();

        $response = $this->actingAs($user)->get(route('packages.success', $otherPackage));

        $response->assertNotFound();
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
