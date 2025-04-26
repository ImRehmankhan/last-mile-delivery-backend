<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ItemDetail extends Model
{
    protected $table = 'itemdetails';
    protected $fillable = [
        'variation_name',
        'price',
        'additional_info',
        'picture',
        'item_ID',
    ];
    public $timestamps = false;
}

