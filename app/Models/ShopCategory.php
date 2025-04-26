<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ShopCategory extends Model
{
    protected $table = 'shopcategory';
    protected $fillable = [
        'name',
    ];
    public $timestamps = false;
}

