<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddApprovalStatusToVendororganizationTable extends Migration
{
    public function up(): void
    {
        Schema::table('vendororganization', function (Blueprint $table) {
            $table->enum('approval_status', ['pending', 'approved', 'rejected'])->default('pending')->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('vendororganization', function (Blueprint $table) {
            $table->dropColumn('approval_status');
        });
    }
}
