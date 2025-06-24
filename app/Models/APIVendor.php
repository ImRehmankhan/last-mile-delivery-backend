<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ApiVendor extends Model
{
    protected $table = 'apivendor';
    protected $fillable = [
        'api_key',
        'api_base_url',
        'api_auth_method',
        'api_version',
        'vendor_integration_status',
        'response_format',
        'branches_ID'
    ];


    public $timestamps = true;
}

