<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up() {
        Schema::create('cart_suborders', function (Blueprint $table) {
            $table->id();
            $table->string('vendor_type', 50);
            $table->unsignedBigInteger('vendor_ID'); 
            $table->unsignedBigInteger('shop_ID'); 
            $table->unsignedBigInteger('branch_ID'); 
            $table->decimal('total_amount', 10, 2);
            $table->decimal('delivery_fee', 10, 2)->default(0.00); // Delivery fee
            $table->unsignedBigInteger('cart_ID'); // Links to cart
        });
    }

    public function down() {
        Schema::dropIfExists('cart_suborders');
    }
};