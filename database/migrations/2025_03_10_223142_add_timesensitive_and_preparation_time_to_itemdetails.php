<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('itemdetails', function (Blueprint $table) {
            $table->enum('timesensitive', ['Yes', 'No'])->default('No')->after('status');
            $table->integer('preparation_time')->default(0)->after('timesensitive');
        });
    }

    public function down()
    {
        Schema::table('itemdetails', function (Blueprint $table) {
            $table->dropColumn(['timesensitive', 'preparation_time']);
        });
    }
};
