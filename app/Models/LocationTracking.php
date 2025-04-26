<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LocationTracking extends Model
{
    protected $table = 'locationtracking';
    protected $fillable = [
        'latitude',
        'longitude',
        'status',
        'time_stamp',
        'suborders_ID',
    ];
    public $timestamps = false;
}

