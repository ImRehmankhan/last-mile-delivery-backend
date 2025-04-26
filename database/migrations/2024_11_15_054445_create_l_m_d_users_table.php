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
    Schema::create('lmd_users', function (Blueprint $table) {
        $table->id(); // Auto-incremented primary key
        $table->string('name', 100);
        $table->string('email', 255)->unique();
        $table->string('phone_no', 15)->unique();
        $table->string('cnic', 15)->unique();
        $table->string('password', 255);
        $table->enum('lmd_user_role', ['customer', 'vendor', 'deliveryboy', 'admin']);
        $table->string('profile_picture', 255)->nullable(); 
        $table->timestamp('account_creation_date')->useCurrent();
    });
}


     
     

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('lmd_users');
    }
};
