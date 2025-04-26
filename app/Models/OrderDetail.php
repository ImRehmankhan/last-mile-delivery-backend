<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrderDetail extends Model
{
    protected $table = 'orderdetails';
    protected $fillable = [
        'quantity',
        'price',
        'total',
        'itemdetails_ID',
        'suborders_ID',
    ];
    public $timestamps = false;
}

