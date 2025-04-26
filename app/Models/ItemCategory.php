<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ItemCategory extends Model
{
    protected $table = 'itemcategories';
    protected $fillable = [
        'name',
    ];
    public $timestamps = false;
}
