<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
return new class extends Migration
{
    /**
     * Run the migrations.
     */
   

    public function up()
    {
        DB::statement("ALTER TABLE lmd_users MODIFY COLUMN lmd_user_role ENUM('customer', 'vendor', 'deliveryboy', 'admin', 'organization')");
    }
    
    public function down()
    {
        DB::statement("ALTER TABLE lmd_users MODIFY COLUMN lmd_user_role ENUM('customer', 'vendor', 'deliveryboy', 'admin')");
    }
    

};
