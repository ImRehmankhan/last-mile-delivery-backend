<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up() {
        Schema::create('cart_items', function (Blueprint $table) {
            $table->id();
            $table->integer('quantity');
            $table->decimal('price', 10, 2);
            $table->decimal('total', 10, 2);
            $table->unsignedBigInteger('itemdetails_ID');
            $table->unsignedBigInteger('cart_suborders_ID'); // Links to cart_suborders
        });
    }

    public function down() {
        Schema::dropIfExists('cart_items');
    }
};