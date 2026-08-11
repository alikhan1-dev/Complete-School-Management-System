<?php

namespace App\Modules\Attendance\Models;

use App\Modules\Shared\Models\BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * CI table spelling preserved: student_attendences.
 */
class StudentAttendence extends BaseModel
{
    protected $table = 'student_attendences';

    public $timestamps = true;

    protected $fillable = [
        'student_session_id',
        'biometric_attendence',
        'qrcode_attendance',
        'date',
        'attendence_type_id',
        'remark',
        'biometric_device_data',
        'user_agent',
        'in_time',
        'out_time',
        'is_active',
    ];

    public function type(): BelongsTo
    {
        return $this->belongsTo(AttendenceType::class, 'attendence_type_id');
    }
}
