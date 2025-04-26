<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SubOrder extends Model
{
    protected $table = 'suborders';
    // protected $fillable = [
    //     'vendor_type',
    //     'vendor_order_id',
    //     'status',
    //     'total_amount',
    //     'estimated_delivery_time',
    //     'delivery_time',
    //     'deliveryboys_ID',
    //     'orders_ID',
    //     'vendor_ID',
    //     'branch_ID',
    // ];


    protected $fillable = [
        'vendor_type',
        'vendor_order_id',
        'status',
        'payment_status',
        'total_amount',
        'estimated_delivery_time',
        'delivery_time',
        'deliveryboys_ID',
        'orders_ID',
        'vendor_ID',
        'shop_ID',
        'branch_ID',
    ];
    public $timestamps = false;
}

