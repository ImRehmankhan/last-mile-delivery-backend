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
        Schema::create('menulog', function (Blueprint $table) {
            $table->id();
            $table->enum('Status', ['ON', 'OFF'])->default('OFF');
            $table->timestamp('StartTime')->useCurrent();
            $table->timestamp('EndTime')->nullable();
            $table->unsignedBigInteger('menu_ID');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('menulog');
    }
};
