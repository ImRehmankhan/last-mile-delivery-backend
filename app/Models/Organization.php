<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Organization extends Model {
    use HasFactory;

    protected $table = 'organizations';

    protected $fillable = [
        'lmd_users_ID'
    ];

    public $timestamps = false; // Since you don't want timestamps
}

