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
        Schema::create('locationtracking', function (Blueprint $table) {
            $table->id();
            $table->float('latitude');
            $table->float('longitude');
           $table->enum('status', ['in_transit', 'reached_destination', 'delivered'])->default('in_transit');
           $table->timestamp('time_stamp')->useCurrent();
            $table->unsignedBigInteger('suborders_ID');
            $table->timestamps();
        });
    }
    

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('locationtracking');
    }
};
