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
        Schema::create('deliveryboyslogs', function (Blueprint $table) {
            $table->id(); // Primary key
            $table->enum('status', ['ON', 'OFF'])->notNullable(); // Status column
            $table->dateTime('start_time')->default(DB::raw('CURRENT_TIMESTAMP')); // Start time with default
            $table->dateTime('end_time')->nullable(); // End time, nullable
            $table->unsignedBigInteger('deliveryboys_ID'); // Foreign key to deliveryboys table
            $table->timestamps(); // Adds created_at and updated_at columns
        });
    }


    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('deliveryboyslogs');
    }
};
