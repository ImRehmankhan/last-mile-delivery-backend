<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Shop extends Model
{
    protected $table = 'shops';
    protected $fillable = [
        'name',
        'description',
        'status',
        'approval_status',
        'shopcategory_ID',
        'vendors_ID',
    ];
    public $timestamps = false;
}

