<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MenuDetail extends Model
{
    protected $table = 'menudetails';
    protected $fillable = [
        'quantity',
        'itemsdetails_ID',
        'menu_ID',
        'branches_ID',
    ];
    public $timestamps = false;
}

