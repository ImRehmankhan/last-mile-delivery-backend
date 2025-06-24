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
 

  Schema::create('vehicle_categories', function (Blueprint $table) {
        $table->id();
        $table->string('name', 50); // e.g. Bike, Car, Truck
        $table->decimal('per_km_charge', 8, 2); // e.g. 25.00
        $table->text('description')->nullable(); // Optional: describe the category
        $table->timestamps();
    });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vehicle_categories');
    }
};
