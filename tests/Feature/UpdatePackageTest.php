<?php

namespace Tests\Feature;

use App\Enums\PackageEventType;
use App\Enums\PackageStatus;
use App\Enums\UserRole;
use App\Models\Branch;
use App\Models\Company;
use App\Models\Package;
use App\Models\PackageCategory;
use App\Models\Seller;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class UpdatePackageTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_owner_updates_allowed_fields_without_changing_control_fields(): void
    {
        [$owner, $package, $seller, $category] = $this->packageContext();
        $originalTrackingCode = $package->tracking_code;
        $originalUlid = $package->ulid;
        $originalCompanyId = $package->company_id;
        $originalBranchId = $package->branch_id;
        $originalReceivedAt = $package->received_at->toDateTimeString();

        $response = $this->actingAs($owner)->put(route('packages.update', $package), [
            ...$this->validPayload($seller, $category),
            'recipient_name' => 'Destinataria actualizada',
            'recipient_phone' => '71111111',
            'storage_code' => ' b2-09 ',
            'description' => 'Descripción actualizada',
            'notes' => 'Notas actualizadas',
            'tracking_code' => 'TIK-MANIPULADO',
            'ulid' => '01MANIPULATED00000000000000',
            'company_id' => Company::factory()->create()->id,
            'delivered_at' => now(),
        ]);

        $response->assertRedirect(route('packages.show', $package));
        $package->refresh();
        $this->assertSame('Destinataria actualizada', $package->recipient_name);
        $this->assertSame('71111111', $package->recipient_phone);
        $this->assertSame('B2-09', $package->storage_code);
        $this->assertSame('Descripción actualizada', $package->description);
        $this->assertSame('Notas actualizadas', $package->notes);
        $this->assertSame($originalTrackingCode, $package->tracking_code);
        $this->assertSame($originalUlid, $package->ulid);
        $this->assertSame($originalCompanyId, $package->company_id);
        $this->assertSame($originalBranchId, $package->branch_id);
        $this->assertSame($originalReceivedAt, $package->received_at->toDateTimeString());
        $this->assertNull($package->delivered_at);
        $event = $package->events()->where('event', PackageEventType::PackageUpdated)->sole();
        $this->assertArrayHasKey('recipient_name', $event->metadata['changes']);
        $this->assertArrayNotHasKey('tracking_code', $event->metadata['changes']);
    }

    /** @return array<string, array{PackageStatus}> */
    public static function immutableStatuses(): array
    {
        return [
            'delivered' => [PackageStatus::Delivered],
            'cancelled' => [PackageStatus::Cancelled],
        ];
    }

    #[DataProvider('immutableStatuses')]
    public function test_delivered_or_cancelled_package_cannot_be_edited(PackageStatus $status): void
    {
        [$owner, $package, $seller, $category] = $this->packageContext($status);

        $response = $this->actingAs($owner)->put(route('packages.update', $package), [
            ...$this->validPayload($seller, $category),
            'recipient_name' => 'No debe cambiar',
        ]);

        $response->assertForbidden();
        $this->assertNotSame('No debe cambiar', $package->fresh()->recipient_name);
        $this->assertDatabaseMissing('package_events', [
            'package_id' => $package->id,
            'event' => PackageEventType::PackageUpdated->value,
        ]);
    }

    public function test_seller_from_another_company_is_rejected(): void
    {
        [$owner, $package, , $category] = $this->packageContext();
        $otherSeller = Seller::factory()->create();

        $response = $this->actingAs($owner)->put(route('packages.update', $package), $this->validPayload($otherSeller, $category));

        $response->assertSessionHasErrors('seller_id');
        $this->assertNotSame($otherSeller->id, $package->fresh()->seller_id);
    }

    public function test_changing_seller_updates_sender_snapshot(): void
    {
        [$owner, $package, , $category] = $this->packageContext();
        $newSeller = Seller::factory()->for($owner->company)->create([
            'name' => 'María Nueva',
            'business_name' => 'Negocio Nuevo',
            'phone' => '72222222',
            'active' => true,
        ]);

        $response = $this->actingAs($owner)->put(route('packages.update', $package), $this->validPayload($newSeller, $category));

        $response->assertRedirect(route('packages.show', $package));
        $package->refresh();
        $this->assertSame($newSeller->id, $package->seller_id);
        $this->assertSame('Negocio Nuevo', $package->sender_name);
        $this->assertSame('72222222', $package->sender_phone);
    }

    public function test_changing_to_inactive_seller_is_rejected(): void
    {
        [$owner, $package, , $category] = $this->packageContext();
        $inactiveSeller = Seller::factory()->for($owner->company)->create(['active' => false]);

        $response = $this->actingAs($owner)->put(route('packages.update', $package), $this->validPayload($inactiveSeller, $category));

        $response->assertSessionHasErrors('seller_id');
        $this->assertNotSame($inactiveSeller->id, $package->fresh()->seller_id);
    }

    public function test_operator_cannot_edit_packages(): void
    {
        [$owner, $package, $seller, $category] = $this->packageContext();
        $operator = User::factory()->for($owner->company)->withRole(UserRole::Operator)->create();

        $this->actingAs($operator)
            ->put(route('packages.update', $package), $this->validPayload($seller, $category))
            ->assertForbidden();
    }

    /** @return array{User, Package, Seller, PackageCategory} */
    private function packageContext(PackageStatus $status = PackageStatus::ReadyForPickup): array
    {
        $company = Company::factory()->create();
        $branch = Branch::factory()->for($company)->create();
        $owner = User::factory()->for($company)->withRole(UserRole::Owner)->create();
        $seller = Seller::factory()->for($company)->create();
        $category = PackageCategory::factory()->for($company)->create();
        $package = Package::factory()->forBranch($branch)->forSeller($seller)->withStatus($status)->create([
            'package_category_id' => $category->id,
            'received_by' => $owner->id,
        ]);

        return [$owner, $package, $seller, $category];
    }

    /** @return array<string, string> */
    private function validPayload(Seller $seller, PackageCategory $category): array
    {
        return [
            'seller_id' => (string) $seller->id,
            'package_category_id' => (string) $category->id,
            'storage_code' => 'A1-01',
            'recipient_name' => 'Destinataria Prueba',
            'recipient_phone' => '70000001',
            'description' => 'Caja mediana',
            'notes' => 'Manipular con cuidado',
        ];
    }
}
