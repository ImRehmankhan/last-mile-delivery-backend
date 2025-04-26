<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DeliveryBoyRating extends Model
{
    protected $table = 'deliveryboysrating';
    protected $fillable = [
        'comments',
        'rating_stars',
        'rating_date',
        'deliveryboys_ID',
        'suborder_ID',
    ];
    public $timestamps = false;
}

