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
        Schema::create('vendororganization', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('vendor_ID'); // Vendor ID
            $table->unsignedBigInteger('organization_ID'); // Organization ID
            $table->enum('status', ['active', 'inactive'])->default('active');
    
     });
    }
    

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vendororganization');
    }
};
