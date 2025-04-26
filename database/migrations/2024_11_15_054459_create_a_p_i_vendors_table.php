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
    Schema::create('apivendor', function (Blueprint $table) {
        $table->id(); // Auto-increment ID
        $table->string('api_key')->unique();
        $table->string('api_base_url')->nullable();
        $table->string('api_auth_method', 50)->nullable();
        $table->string('api_version', 10)->nullable();
        $table->string('vendor_integration_status', 50)->default('Active');
        $table->enum('response_format', ['JSON', 'XML'])->default('JSON'); // Added response_format column
        $table->unsignedBigInteger('branches_ID');
        $table->timestamps(); // Created at & Updated at
    });

    Schema::create('apimethods', function (Blueprint $table) {
        $table->id(); // Auto-increment ID
        $table->string('method_name', 100);// place order,getorder 
        $table->enum('http_method', ['GET', 'POST', 'PUT', 'DELETE']);
        $table->string('endpoint'); //  api/getorder
        $table->string('description')->nullable();
        $table->unsignedBigInteger('apivendor_ID');
        $table->timestamps(); // Created at & Updated at
    });


}


    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('apivendor');
         Schema::dropIfExists('apimethods');
    }
};
