<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Vendor extends Model
{
    protected $table = 'vendors';
    protected $fillable = [
        'vendor_type',
        'lmd_users_ID',
    ];
    public $timestamps = false;
}

