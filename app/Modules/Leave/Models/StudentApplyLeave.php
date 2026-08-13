<?php

namespace App\Modules\Leave\Models;

use App\Modules\Shared\Models\BaseModel;

/**
 * CI table: student_applyleave.
 * status: 0=pending, 1=approved, 2=disapproved
 * request_type: 0=student, 1=staff
 */
class StudentApplyLeave extends BaseModel
{
    protected $table = 'student_applyleave';

    public $timestamps = true;

    protected $fillable = [
        'student_session_id',
        'from_date',
        'to_date',
        'apply_date',
        'status',
        'docs',
        'reason',
        'approve_by',
        'approve_date',
        'request_type',
    ];
}
