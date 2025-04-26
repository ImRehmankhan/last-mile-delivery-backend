<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CourierOrder extends Model
{
    protected $table = 'courierorder';

    public $timestamps = false;

    protected $fillable = [
        'pickup_address_ID',
        'delivery_address_ID',
        'order_status',
        'customer_confirmed',
        'pickup_time',
        'delivery_time',
        'payment_method',
        'estimated_delivery_time',
        'customers_ID',
        'deliveryboys_ID',
        'item_ID'
    ];
}

