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
        Schema::create('menudetails', function (Blueprint $table) {
            $table->id();
            $table->string('quantity', 50);
            $table->unsignedBigInteger('itemsdetails_ID');
            $table->unsignedBigInteger('menu_ID');
            $table->unsignedBigInteger('branches_ID');
            $table->timestamps();
        });
    }
    

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('menudetails');
    }
};
