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
        Schema::table('packages', function (Blueprint $table) {
            $table->foreignId('package_category_id')->nullable()->after('branch_id')->constrained()->restrictOnDelete();
            $table->string('storage_code', 50)->nullable()->after('tracking_code');
            $table->decimal('storage_price', total: 12, places: 2)->nullable()->after('storage_code');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('packages', function (Blueprint $table) {
            $table->dropConstrainedForeignId('package_category_id');
            $table->dropColumn(['storage_code', 'storage_price']);
        });
    }
};
