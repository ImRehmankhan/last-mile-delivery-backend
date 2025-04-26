<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RestrictedItems extends Model
{
    protected $table = 'restricteditems';
    protected $fillable = [
        'item_ID',
        'branches_ID'
    ];
    public $timestamps = false;
}