<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CourierAdditionalinfo extends Model
{
    protected $table = 'courieradditionalinfo';

    protected $fillable = [
        'courierorder_ID',
        'package_weight',
        'height',
        'width',
        'depth'
    ];
    public $timestamps = false;
}

