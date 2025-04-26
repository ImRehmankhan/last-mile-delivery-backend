<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    protected $table = 'orders';
    protected $fillable = [
        'order_date',
        'total_amount',
        'order_status',
        // 'estimated_delivery_time',
        // 'delivery_time',
        'order_type',
        'payment_status',
        'payment_method',
        'customers_ID',
        'addresses_ID',
    ];


   
    public $timestamps = false;
}

