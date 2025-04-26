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
    
         // Create itemcategories table
    Schema::create('itemcategories', function (Blueprint $table) {
        $table->id();
        $table->string('name'); // E.g., "Beverages", "Snacks", "Fast Food"
        $table->unsignedBigInteger('shop_category_ID')->nullable();
    });

    // Create items table
    Schema::create('items', function (Blueprint $table) {
        $table->id();
        $table->string('name'); // Name of the item (e.g., "Burger", "Pizza")
        $table->text('description')->nullable(); // General description of the item
        $table->unsignedBigInteger('category_ID')->nullable();
        $table->unsignedBigInteger('branches_ID')->nullable();
    
    });
    }
    

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('items'); 
        Schema::dropIfExists('itemcategories');
    }
};
