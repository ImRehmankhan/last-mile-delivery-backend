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
    Schema::create('branchesrejectionreasons', function (Blueprint $table) {
        $table->id();
        $table->string('reason', 255)->nullable();
        $table->unsignedBigInteger('branches_ID');
        $table->enum('status', ['Pending', 'Corrected'])->default('Pending'); // Add status column
        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('branchesrejectionreasons');
    }
};
