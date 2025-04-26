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
    Schema::create('mapping', function (Blueprint $table) {
        $table->id();
        $table->string('api_values', 255);
        $table->unsignedBigInteger('variable_ID')->nullable();
        $table->unsignedBigInteger('apivendor_ID')->nullable();
        $table->unsignedBigInteger('branch_ID')->nullable();
        $table->timestamps();
    });
}


    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mapping');
    }
};
