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
        Schema::table('package_categories', function (Blueprint $table) {
            $table->decimal('commission_rate', total: 12, places: 2)->nullable()->after('price');
        });

        DB::table('package_categories')->where('name', 'Pequeño')->update(['commission_rate' => '0.50']);
        DB::table('package_categories')->where('name', 'Mediano')->update(['commission_rate' => '0.70']);
        DB::table('package_categories')->where('name', 'Grande')->update(['commission_rate' => '1.00']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('package_categories', function (Blueprint $table) {
            $table->dropColumn('commission_rate');
        });
    }
};
