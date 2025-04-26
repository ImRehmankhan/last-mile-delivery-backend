<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LMDUser extends Model
{
    protected $table = 'lmd_users';
    protected $fillable = [
        'name',
        'email',
        'phone_no',
        'cnic',
        'password',
        'lmd_user_role',
        'profile_picture',
        'account_creation_date',
    ];
    public $timestamps = false;

}

