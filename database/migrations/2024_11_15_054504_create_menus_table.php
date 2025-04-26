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
    Schema::create('menu', function (Blueprint $table) {
        $table->id();
        $table->string('meal_type', 50);
        $table->date('start_date');
        $table->date('end_date')->nullable();
        $table->dateTime('order_time')->nullable();
        $table->string('status', 50)->default('Active');
        $table->unsignedBigInteger('orders_ID');
        $table->timestamps();
    });
}


    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('menu');
    }
};
