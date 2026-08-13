<?php

namespace App\Modules\Leave\Models;

use App\Modules\Shared\Models\BaseModel;

/**
 * CI table: staff_leave_request.
 */
class StaffLeaveRequest extends BaseModel
{
    protected $table = 'staff_leave_request';

    public $timestamps = true;

    protected $fillable = [
        'staff_id',
        'leave_type_id',
        'leave_from',
        'leave_to',
        'leave_days',
        'employee_remark',
        'admin_remark',
        'approve_date',
        'status',
        'applied_by',
        'document_file',
        'session_id',
        'date',
        'half_day_leave',
    ];
}
