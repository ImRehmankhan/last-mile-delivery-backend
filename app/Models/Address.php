<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Address extends Model
{
    protected $table = 'addresses';
        protected $fillable = [
            'address_type',
            'street',
            'city',
            'zip_code',
            'country',
            'latitude',
            'longitude',
            'lmd_users_ID',
        ];
        public $timestamps = false;

}
