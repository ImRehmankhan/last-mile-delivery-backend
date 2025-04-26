<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Branch extends Model
{
    protected $table = 'branches';
    protected $fillable = [
        'latitude',
        'longitude',
        'description',
        'opening_hours',
        'closing_hours',
        'contact_number',
        'status',
        'approval_status',
        'branch_picture',
        'shops_ID',
        'area_ID',
    ];
    public $timestamps = false;
}

