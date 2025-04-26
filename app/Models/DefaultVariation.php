<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DefaultVariation extends Model
{
    use HasFactory;

    protected $table = 'defaultvariations';

    protected $fillable = [
        'name',
        'itemcategory_ID',
    ];

    public $timestamps = false; // Since you don't want timestamps in your tables
}

