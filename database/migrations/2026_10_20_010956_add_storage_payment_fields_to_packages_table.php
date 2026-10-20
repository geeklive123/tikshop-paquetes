<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('packages', function (Blueprint $table) {
            $table->string('payment_status', 20)->nullable()->after('status');
            $table->timestamp('paid_at')->nullable()->after('cancelled_at');
            $table->foreignId('paid_by')->nullable()->after('cancelled_by')->constrained('users')->nullOnDelete();
            $table->decimal('paid_amount', 12, 2)->nullable()->after('final_storage_amount');

            $table->index(['company_id', 'payment_status'], 'packages_company_payment_status_index');
            $table->index(['company_id', 'paid_at'], 'packages_company_paid_at_index');
        });

        DB::table('packages')
            ->whereIn('status', ['delivered', 'cancelled'])
            ->update(['payment_status' => 'legacy']);

        DB::table('packages')
            ->whereIn('status', ['received', 'ready_for_pickup'])
            ->whereNotNull('package_category_id')
            ->whereNotNull('storage_price')
            ->whereNotNull('storage_code')
            ->where('storage_code', '<>', '')
            ->update(['payment_status' => 'pending']);

        DB::table('packages')
            ->whereNull('payment_status')
            ->update(['payment_status' => 'legacy']);

        Schema::table('packages', function (Blueprint $table) {
            $table->string('payment_status', 20)->nullable()->default('pending')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('packages', function (Blueprint $table) {
            $table->dropForeign(['paid_by']);
            $table->dropIndex('packages_company_payment_status_index');
            $table->dropIndex('packages_company_paid_at_index');
            $table->dropColumn(['payment_status', 'paid_at', 'paid_by', 'paid_amount']);
        });
    }
};
