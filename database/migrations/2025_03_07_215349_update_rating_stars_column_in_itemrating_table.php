<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::table('itemrating', function (Blueprint $table) {
            $table->decimal('rating_stars', 10, 2)->change(); // Change column type
        });
    }

    public function down()
    {
        Schema::table('itemrating', function (Blueprint $table) {
            $table->integer('rating_stars')->change(); // Revert back if needed
        });
    }
};