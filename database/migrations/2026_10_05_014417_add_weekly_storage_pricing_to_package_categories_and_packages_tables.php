<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('package_categories', function (Blueprint $table) {
            $table->decimal('weekly_storage_increment', 12, 2)->default(0)->after('commission_rate');
        });

        Schema::table('packages', function (Blueprint $table) {
            $table->decimal('weekly_storage_increment', 12, 2)->default(0)->after('storage_price');
            $table->decimal('final_storage_amount', 12, 2)->nullable()->after('weekly_storage_increment');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('packages', function (Blueprint $table) {
            $table->dropColumn(['weekly_storage_increment', 'final_storage_amount']);
        });

        Schema::table('package_categories', function (Blueprint $table) {
            $table->dropColumn('weekly_storage_increment');
        });
    }
};
