<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up() {
        Schema::create('defaultvariations', function (Blueprint $table) {
            $table->string('name'); // Example: "Small", "Large", "128GB"
            $table->unsignedBigInteger('itemcategory_ID'); // Links to itemcategories table
        });
    }

    public function down() {
        Schema::dropIfExists('defaultvariations');
    }
};
