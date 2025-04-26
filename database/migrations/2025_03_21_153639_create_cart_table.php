<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up() {
        Schema::create('cart', function (Blueprint $table) {
            $table->id();
            $table->timestamp('cart_date')->useCurrent();
            $table->decimal('total_amount', 10, 2);
            $table->enum('cart_status', ['pending', 'processed'])->default('pending'); 
            $table->unsignedBigInteger('customers_ID'); 
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate(); // Auto-update timestamp
        });
    }

    public function down() {
        Schema::dropIfExists('cart');
    }
};