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
        Schema::table('deliveryboys', function (Blueprint $table) {
            $table->unsignedBigInteger('organization_id')->nullable()->after('lmd_users_ID'); // Add after existing column
        });
    }
    

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::table('deliveryboys', function (Blueprint $table) {
            $table->dropColumn('organization_id');
        });
    }
    
};
