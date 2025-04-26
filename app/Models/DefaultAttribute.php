<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class DefaultAttribute extends Model
{
    use HasFactory;

    protected $table = 'defaultattributes'; // Specify the table name

    protected $fillable = [
        'itemcategory_ID',
        'key_name',
        'value',
    ];
}

