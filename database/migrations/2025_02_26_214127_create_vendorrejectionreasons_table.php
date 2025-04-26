<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::create('vendorrejectionreasons', function (Blueprint $table) {
            $table->id();
            $table->string('reason', 255)->nullable();
            $table->unsignedBigInteger('vendors_ID'); // Links to vendors (Logical relationship, no FK)
            $table->enum('status', ['Pending', 'Corrected'])->default('Pending');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('vendorrejectionreasons');
    }
};
