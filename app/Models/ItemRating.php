<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ItemRating extends Model
{
    protected $table = 'itemrating';
    protected $fillable = [
        'comments',
        'rating_stars',
        'rating_date',
        'suborders_ID',
        'itemdetails_ID',
    ];
    public $timestamps = false;
}

