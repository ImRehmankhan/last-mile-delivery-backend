<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ApiMethod extends Model
{
    protected $table = 'api_methods';

    // Define the fillable attributes
    protected $fillable = [
        'method_name',
        'http_method',
        'endpoint',
        'description',
        'apivendor_ID'
    ];


    public $timestamps = false;
}

