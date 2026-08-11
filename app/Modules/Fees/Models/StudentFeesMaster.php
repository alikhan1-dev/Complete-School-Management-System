<?php

namespace App\Modules\Fees\Models;

use App\Modules\Shared\Models\BaseModel;

class StudentFeesMaster extends BaseModel
{
    protected $table = 'student_fees_master';

    public $timestamps = true;

    protected $fillable = [
        'is_system',
        'student_session_id',
        'fee_session_group_id',
        'amount',
        'is_active',
    ];
}
