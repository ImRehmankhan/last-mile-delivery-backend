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
        Schema::create('itemrating', function (Blueprint $table) {
            $table->id();
            $table->string('comments', 255)->nullable();
            // $table->integer('rating_stars');
            $table->decimal('rating_stars', 10, 2);
            $table->timestamp('rating_date')->useCurrent();
            $table->unsignedBigInteger('suborders_ID');
            $table->unsignedBigInteger('itemdetails_ID');
            $table->timestamps();
        });
    }
    

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('itemrating');
    }
};
