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
    Schema::create('branches', function (Blueprint $table) {
        $table->id(); // ID (Primary Key)
        $table->decimal('latitude', 9, 6)->nullable(); // Latitude (more precise)
        $table->decimal('longitude', 9, 6)->nullable(); // Longitude (more precise)
        $table->string('description', 255)->nullable(); // Description
        $table->time('opening_hours')->nullable(); // Opening hours
        $table->time('closing_hours')->nullable(); // Closing hours
        $table->string('contact_number', 15)->nullable(); // Contact number
        $table->enum('status', ['active', 'inactive'])->default('active'); // Status
        $table->enum('approval_status', ['pending', 'approved', 'rejected'])->default('pending'); // Approval status
        $table->string('branch_picture', 255)->nullable(); // Branch picture
        $table->unsignedBigInteger('shops_ID'); // Links to shops (Logical relationship, no FK)
        $table->unsignedBigInteger('area_ID'); // Links to area (Logical relationship, no FK)
    });
}


    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('branches');
    }
};
