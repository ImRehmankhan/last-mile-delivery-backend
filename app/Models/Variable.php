<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Variable extends Model
{
    protected $table = 'variables';
    protected $fillable = [
        'tags',
    ];
        public $timestamps = true;

}

