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

         // Create item_details table
    Schema::create('itemdetails', function (Blueprint $table) {
        $table->id();
        $table->string('variation_name'); // Name of the variation (e.g., "Small", "Large")
        $table->decimal('price', 10, 2); // Price of the variation
        $table->string('additional_info',255)->nullable(); // Any additional information about the item
        $table->string('picture')->nullable(); // Image for the variation
        $table->enum('status', ['enabled', 'disabled'])->default('enabled'); // Status column with default 'enabled'
      /*   $table->enum('timesensitive', ['Yes', 'No'])->default('No')->after('status');
        $table->integer('preparation_time')->default(0)->after('timesensitive'); */
        $table->unsignedBigInteger('item_ID'); // Links to Items table
    });

    // Create stock table
    Schema::create('stock', function (Blueprint $table) {
        $table->id();
        $table->unsignedBigInteger('item_detail_ID'); // Links to Item Details table
        $table->integer('stock_qty')->default(0); // Stock quantity
        $table->timestamp('last_updated')->useCurrent()->useCurrentOnUpdate(); // Tracks last update
    });
    }
    

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('itemdetails');
        Schema::dropIfExists('stock');
    }
};
