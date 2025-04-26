<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateVendororganizationrejectionreasonsTable extends Migration
{
    public function up(): void
    {
        Schema::create('vendororganizationrejectionreasons', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('vendororganization_ID'); // FK to request table
            $table->string('reason', 255)->nullable();
            $table->enum('status', ['Pending', 'Corrected'])->default('Pending');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vendororganizationrejectionreasons');
    }
}
