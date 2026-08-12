<?php

namespace App\Modules\Attendance\Models;

use App\Modules\Shared\Models\BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * CI table: staff_attendance.
 */
class StaffAttendance extends BaseModel
{
    protected $table = 'staff_attendance';

    public $timestamps = true;

    protected $fillable = [
        'date',
        'staff_id',
        'staff_attendance_type_id',
        'biometric_attendence',
        'qrcode_attendance',
        'biometric_device_data',
        'user_agent',
        'remark',
        'is_active',
        'in_time',
        'out_time',
    ];

    public function type(): BelongsTo
    {
        return $this->belongsTo(StaffAttendanceType::class, 'staff_attendance_type_id');
    }
}
