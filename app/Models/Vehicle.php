<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Vehicle extends Model
{
    protected $table = 'vehicles';
    protected $fillable = [
        'plate_no',
        'color',
        'vehicle_type',
        'model',
        'deliveryboys_ID',
    ];
    public $timestamps = false;
}

