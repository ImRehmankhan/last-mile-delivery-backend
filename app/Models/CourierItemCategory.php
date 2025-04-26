<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CourierItemCategory extends Model
{
    protected $table = 'courieritemcategory';

    protected $fillable = [
        'category_name'
    ];
    public $timestamps = false;
}

