<?php

namespace App\Modules\Staff\Models;

use App\Modules\Shared\Models\BaseModel;

class StaffDesignation extends BaseModel
{
    protected $table = 'staff_designation';

    protected $fillable = [
        'designation',
        'is_active',
    ];
}
