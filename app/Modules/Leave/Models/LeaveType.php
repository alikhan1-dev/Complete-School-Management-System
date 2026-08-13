<?php

namespace App\Modules\Leave\Models;

use App\Modules\Shared\Models\BaseModel;

/**
 * CI table: leave_types.
 */
class LeaveType extends BaseModel
{
    protected $table = 'leave_types';

    public $timestamps = false;

    protected $fillable = [
        'type',
        'is_active',
    ];
}
