<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CourierImage extends Model
{
    protected $table = 'courierimages';
    protected $fillable = [
        'image_url',
        'courierorder_ID',
    ];
    public $timestamps = false;
}

