<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CourierItem extends Model
{
    protected $table = 'courieritem';

    protected $fillable = [
        'courieritemcategory_ID',
        'item_name',
        'additonal_info'
    ];
    public $timestamps = false;
}

