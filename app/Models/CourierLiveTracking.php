<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CourierLiveTracking extends Model
{
    protected $table = 'courierlivetracking';

    protected $fillable = [
        'courierorder_ID',
        'latitude',
        'longitude',
        'time_stamp',
    ];
    public $timestamps = false;
}

