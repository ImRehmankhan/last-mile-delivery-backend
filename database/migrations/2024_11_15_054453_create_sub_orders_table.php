<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('suborders', function (Blueprint $table) {
            $table->id();
            $table->string('vendor_type', 50);
            $table->string('vendor_order_id', 50)->nullable();
         $table->enum('status', [
                 'pending','in_progress', 'ready',
                'assigned','picked_up','handover_confirmed', 
                'in_transit','delivered','cancelled', ])->default('pending');
                $table->enum('payment_status', [
                    'pending','confirmed_by_customer','confirmed_by_deliveryboy',
                    'confirmed_by_vendor',
                ])->default('pending');
            $table->decimal('total_amount', 10, 2);
            $table->dateTime('estimated_delivery_time')->nullable();
            $table->dateTime('delivery_time')->nullable();
            $table->unsignedBigInteger('deliveryboys_ID')->nullable();
            $table->unsignedBigInteger('orders_ID');
            $table->unsignedBigInteger('vendor_ID'); 
            $table->unsignedBigInteger('shop_ID'); 
            $table->unsignedBigInteger('branch_ID'); 
            $table->timestamps();
        });
        
    }
    
    

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('suborders');
    }
};
