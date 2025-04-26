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
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->timestamp('order_date')->useCurrent();
            $table->decimal('total_amount', 10, 2);
            $table->enum('order_status', ['pending', 'confirmed', 'completed', 'cancelled'])->default('pending');
            // $table->dateTime('estimated_delivery_time')->nullable();
            // $table->dateTime('delivery_time')->nullable();
             $table->enum('order_type', ['schedule', 'one-time', 'menu'])->default('one-time');
            $table->enum('payment_status', ['pending', 'completed', 'failed'])->default('pending');
            $table->enum('payment_method', ['cash_on_delivery'])->default('cash_on_delivery');
            $table->unsignedBigInteger('customers_ID');
            $table->unsignedBigInteger('addresses_ID');
            $table->timestamps();
        });
    }
    


    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
