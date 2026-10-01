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
        Schema::create('seller_commission_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('settlement_id')->constrained('seller_commission_settlements')->cascadeOnDelete();
            $table->foreignId('package_id')->unique()->constrained()->restrictOnDelete();
            $table->string('category_name_snapshot', 100);
            $table->decimal('commission_rate', total: 12, places: 2);
            $table->decimal('commission_amount', total: 12, places: 2);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('seller_commission_items');
    }
};
