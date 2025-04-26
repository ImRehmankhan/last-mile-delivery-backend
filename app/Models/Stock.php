<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Stock extends Model
{
    protected $table = 'stock';
    protected $fillable = [
        'item_detail_ID',
        'stock_qty',
        'last_updated',
    ];
    public $timestamps = false;
}

