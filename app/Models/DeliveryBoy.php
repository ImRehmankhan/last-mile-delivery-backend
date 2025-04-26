<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DeliveryBoy extends Model
{
    protected $table = 'deliveryboys';
    protected $fillable = [
        'total_deliveries',
        'license_no',
        'license_expiration_date',
        'status',
        'approval_status',
        'license_front',
        'license_back',
        'lmd_users_ID',
    ];
    public $timestamps = false;
}
