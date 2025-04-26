<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VendorOrganization extends Model {
    use HasFactory;

    protected $table = 'vendororganization';

    protected $fillable = [
        'vendor_ID',
        'organization_ID',
        'status'
    ];

    public $timestamps = false; // No timestamps
}

