<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VendorRejectionReason extends Model
{
    protected $table = 'vendorrejectionreasons'; // Explicit table name

    protected $fillable = ['reason', 'vendors_ID', 'status']; // Add necessary columns
}
