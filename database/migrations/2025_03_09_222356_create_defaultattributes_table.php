<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::create('defaultattributes', function (Blueprint $table) {
            $table->id(); // Auto-increment primary key
            $table->unsignedBigInteger('itemcategory_ID'); // No foreign key constraint
            $table->string('key_name'); // Attribute name (e.g., "RAM")
            $table->string('value'); // Attribute value (e.g., "16GB")
        });
    }

    public function down()
    {
        Schema::dropIfExists('defaultattributes');
    }
};
