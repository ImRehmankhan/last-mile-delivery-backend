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
    Schema::create('deliveryboys', function (Blueprint $table) {
        $table->id(); // ID (Primary Key)
        $table->integer('total_deliveries')->default(0); // Total deliveries
        $table->string('license_no', 20); // License Number
        $table->date('license_expiration_date')->nullable(); // License Expiration Date
        $table->enum('status', ['Available', 'Not Available'])->default('Available'); // Status
        $table->enum('approval_status', ['pending', 'approved', 'rejected'])->default('pending'); // Approval Status
        $table->string('license_front', 255)->nullable(); // License Front Image
        $table->string('license_back', 255)->nullable(); // License Back Image
        $table->unsignedBigInteger('lmd_users_ID'); // Links to lmd_users (Logical relationship, no FK)
    });
}


     
     


    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('deliveryboys');
    }
};
