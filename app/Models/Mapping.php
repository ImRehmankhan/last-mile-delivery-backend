<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Mapping extends Model
{
    protected $table = 'mapping';
    protected $fillable = [
        'api_values',
        'variable_ID',
        'apivendor_ID',
        'branch_ID',
    ];
    public $timestamps = true;
}

