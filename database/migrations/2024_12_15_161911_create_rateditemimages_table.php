<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('rateditemimages', function (Blueprint $table) {
            $table->id(); // Auto-incremented primary key
            $table->string('image_path', 255); // Path for the image
            $table->unsignedBigInteger('suborders_ID'); // Foreign key reference to suborders
            $table->unsignedBigInteger('itemdetails_ID'); // Foreign key reference to item details
            $table->timestamps(); // Created at and updated at columns
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rateditemimages');
    }
};
