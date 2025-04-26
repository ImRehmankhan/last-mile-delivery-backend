<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ScheduleOrder extends Model
{
    protected $table = 'scheduleorder';
    protected $fillable = [
        'schedule_type',
        'start_date',
        'next_order_date',
        'end_date',
        'status',
        'orders_ID',
    ];
    public $timestamps = false;
}

