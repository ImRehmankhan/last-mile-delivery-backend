<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BranchRejectionReason extends Model {
    protected $table = 'branchesrejectionreasons';
    protected $fillable = [
        'reason',
        'branches_ID',
        'status',
    ];
    public $timestamps = false;
}
