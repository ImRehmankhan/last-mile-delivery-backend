<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Menu extends Model
{
    protected $table = 'menu';
    protected $fillable = [
        'meal_type',
        'start_date',
        'end_date',
        'order_time',
        'status',
        'orders_ID',
    ];
    public $timestamps = false;
}

