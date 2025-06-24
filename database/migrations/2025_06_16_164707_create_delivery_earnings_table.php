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
        Schema::create('delivery_earnings', function (Blueprint $table) {
        $table->id();
        $table->unsignedBigInteger('deliveryboy_id');  // FK to deliveryboys
        $table->unsignedBigInteger('suborder_id');     // FK to suborders
        $table->decimal('distance_km', 8, 2);           // Distance in kilometers
        $table->decimal('rate_per_km', 8, 2);           // The per-km rate used
        $table->decimal('total_earning', 10, 2);        // distance * rate
        $table->timestamps();
    });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('delivery_earnings');
    }
};
