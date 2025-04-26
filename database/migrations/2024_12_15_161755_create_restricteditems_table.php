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
        Schema::create('restricteditems', function (Blueprint $table) {
            $table->id(); // Auto-incremented primary key
            $table->unsignedBigInteger('item_ID'); // Foreign key reference to item ID
            $table->unsignedBigInteger('branches_ID'); // Foreign key reference to branch ID
            $table->timestamps(); // Created at and updated at columns
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('restricteditems');
    }
};
