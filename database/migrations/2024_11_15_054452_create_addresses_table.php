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
    Schema::create('addresses', function (Blueprint $table) {
        $table->id();
        $table->string('address_type', 50)->nullable();
        $table->string('street', 255);
        $table->string('city', 100);
        $table->string('zip_code', 10)->nullable();
        $table->string('country', 100)->default('Pakistan');
        $table->float('latitude')->nullable();
        $table->float('longitude')->nullable();
        $table->unsignedBigInteger('lmd_users_ID');
        $table->timestamps();
    });
}

    

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('addresses');
    }
};
