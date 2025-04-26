<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::create('vehicles', function (Blueprint $table) {
            $table->id();
            $table->string('plate_no', 20)->unique();
            $table->string('color', 50)->nullable();
            $table->string('vehicle_type', 50)->nullable();
            $table->string('model')->nullable();
            $table->unsignedBigInteger('deliveryboys_ID');
            $table->timestamps();
        });
    }
    

    


    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vehicles');
    }
};
