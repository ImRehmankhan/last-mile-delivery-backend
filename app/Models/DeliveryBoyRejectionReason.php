<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DeliveryBoyRejectionReason extends Model {
    protected $table = 'deliveryboysrejectionreasons';
    protected $fillable = [
        'reason',
        'deliveryboys_ID',
        'status',
    ];
    public $timestamps = false;
}
