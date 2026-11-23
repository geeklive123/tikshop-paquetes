<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('package_categories', function (Blueprint $table): void {
            $table->unsignedSmallInteger('storage_increment_interval_weeks')->default(1)->after('weekly_storage_increment');
        });

        Schema::table('packages', function (Blueprint $table): void {
            $table->unsignedSmallInteger('storage_increment_interval_weeks')->default(1)->after('weekly_storage_increment');
        });
    }

    public function down(): void
    {
        Schema::table('packages', function (Blueprint $table): void {
            $table->dropColumn('storage_increment_interval_weeks');
        });

        Schema::table('package_categories', function (Blueprint $table): void {
            $table->dropColumn('storage_increment_interval_weeks');
        });
    }
};
