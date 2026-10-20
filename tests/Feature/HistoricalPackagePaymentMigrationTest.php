<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class HistoricalPackagePaymentMigrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_migration_classifies_historical_packages_without_inventing_payments(): void
    {
        $migration = require database_path('migrations/2026_10_20_010956_add_storage_payment_fields_to_packages_table.php');
        $migration->down();
        $companyId = DB::table('companies')->insertGetId(['ulid' => (string) Str::ulid(), 'name' => 'Histórica', 'created_at' => now(), 'updated_at' => now()]);
        $branchId = DB::table('branches')->insertGetId(['ulid' => (string) Str::ulid(), 'company_id' => $companyId, 'name' => 'Principal', 'active' => true, 'created_at' => now(), 'updated_at' => now()]);
        $categoryId = DB::table('package_categories')->insertGetId([
            'ulid' => (string) Str::ulid(), 'company_id' => $companyId, 'name' => 'Mediano', 'code_prefix' => 'M',
            'code_start' => 1, 'code_end' => 10, 'price' => 5, 'commission_rate' => 0,
            'weekly_storage_increment' => 1, 'active' => true, 'created_at' => now(), 'updated_at' => now(),
        ]);
        $base = [
            'ulid' => (string) Str::ulid(), 'company_id' => $companyId, 'branch_id' => $branchId,
            'sender_name' => 'Remitente', 'sender_phone' => '70000000', 'recipient_name' => 'Destinatario',
            'recipient_phone' => '71111111', 'received_at' => now(), 'created_at' => now(), 'updated_at' => now(),
            'weekly_storage_increment' => 1,
        ];
        DB::table('packages')->insert([
            [...$base, 'ulid' => (string) Str::ulid(), 'tracking_code' => 'HIST-DELIVERED', 'status' => 'delivered', 'package_category_id' => $categoryId, 'storage_code' => 'M1', 'storage_price' => 5],
            [...$base, 'ulid' => (string) Str::ulid(), 'tracking_code' => 'HIST-CANCELLED', 'status' => 'cancelled', 'package_category_id' => $categoryId, 'storage_code' => 'M2', 'storage_price' => 5],
            [...$base, 'ulid' => (string) Str::ulid(), 'tracking_code' => 'HIST-COMPLETE', 'status' => 'ready_for_pickup', 'package_category_id' => $categoryId, 'storage_code' => 'M3', 'storage_price' => 5],
            [...$base, 'ulid' => (string) Str::ulid(), 'tracking_code' => 'HIST-INCOMPLETE', 'status' => 'ready_for_pickup', 'package_category_id' => null, 'storage_code' => null, 'storage_price' => null],
        ]);

        $migration->up();

        $this->assertSame([
            'HIST-CANCELLED' => 'legacy',
            'HIST-COMPLETE' => 'pending',
            'HIST-DELIVERED' => 'legacy',
            'HIST-INCOMPLETE' => 'legacy',
        ], DB::table('packages')->orderBy('tracking_code')->pluck('payment_status', 'tracking_code')->all());
        $this->assertSame(0, DB::table('packages')->where('payment_status', 'paid')->count());
    }
}
