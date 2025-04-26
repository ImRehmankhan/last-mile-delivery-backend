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
    // Create courierorder table
 

    Schema::create('courierorder', function (Blueprint $table) {
        $table->id();
        $table->unsignedBigInteger('pickup_address_ID');
        $table->unsignedBigInteger('delivery_address_ID');
        $table->enum('order_status', ['pending', 'assigned', 'picked_up', 'delivered'])->default('pending');
        $table->boolean('customer_confirmed')->default(false);    
        $table->dateTime('pickup_time')->nullable();
        $table->dateTime('delivery_time')->nullable();
        $table->string('payment_method', 50)->default('cash_on_delivery');
        $table->decimal('deliveryboy_amount', 8, 2)->default(0);
        $table->dateTime('estimated_delivery_time')->nullable();
        $table->unsignedBigInteger('customers_ID');
        $table->unsignedBigInteger('deliveryboys_ID')->nullable(); 
        $table->unsignedBigInteger('item_ID'); 
  });

    
    // Create courieradditionalinfo table
    Schema::create('courieradditionalinfo', function (Blueprint $table) {
        $table->id();
        $table->unsignedBigInteger('courierorder_ID');
        $table->decimal('package_weight', 10, 2)->nullable();
        $table->decimal('height', 10, 2)->nullable(); // Height in inches or cm
        $table->decimal('width', 10, 2)->nullable(); // Width in inches or cm
        $table->decimal('depth', 10, 2)->nullable(); // Depth in inches or cm
    });

    // Create courierlivetracking table
    Schema::create('courierlivetracking', function (Blueprint $table) {
        $table->id();
        $table->unsignedBigInteger('courierorder_ID');
        $table->decimal('latitude', 10, 7); 
        $table->decimal('longitude', 10, 7); 
        $table->timestamp('time_stamp')->useCurrent(); 
    });


// Create courieritem table
Schema::create('courieritem', function (Blueprint $table) {
    $table->id();
    $table->unsignedBigInteger('courieritemcategory_ID'); // Link to the item category
    $table->string('item_name'); // Name of the item (e.g., "Glassware")
    $table->string('additonal_info'); // Additional info about the item (e.g., "Fragile")
});

    // Create courieritemcategory table
    Schema::create('courieritemcategory', function (Blueprint $table) {
        $table->id();
        $table->string('category_name'); // e.g., "Documents", "Fragile", "Electronics"
    });
}


    public function down()
{
    // Drop courierorder table
    Schema::dropIfExists('courierorder');
    
    
    // Drop courieradditionalinfo table
    Schema::dropIfExists('courieradditionalinfo');
    
    // Drop courierlivetracking table
    Schema::dropIfExists('courierlivetracking');
    
    // Drop courieritem table
    Schema::dropIfExists('courieritem');
    
    // Drop courieritemcategory table
    Schema::dropIfExists('courieritemcategory');
}

};
