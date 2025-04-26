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
        Schema::create('scheduleorder', function (Blueprint $table) {
            $table->id();
            $table->enum('schedule_type', ['daily', 'weekly', 'monthly']);
            $table->date('start_date');
            $table->date('next_order_date');
            $table->date('end_date')->nullable();
            $table->string('status', 50)->default('Active');
            $table->unsignedBigInteger('orders_ID');
            $table->timestamps();
        });
        // Schema::create('scheduleorder', function (Blueprint $table) {
        //     $table->id();
        //     $table->enum('schedule_type', ['daily', 'weekly', 'monthly']);
        //     $table->date('start_date');
        //     $table->date('next_order_date');
        //     $table->date('end_date')->nullable();
        //     $table->enum('status', ['active', 'processed', 'cancelled'])->default('active');
        //     $table->date('last_processed_date')->nullable();
        //     $table->unsignedBigInteger('orders_ID');
        //     $table->timestamps();
        // });
    }
    

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('scheduleorder');
    }
};
