<?php

namespace App\Modules\Attendance\Models;

use App\Modules\Shared\Models\BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * CI table: student_subject_attendances (period / subject attendance).
 */
class StudentSubjectAttendance extends BaseModel
{
    protected $table = 'student_subject_attendances';

    public $timestamps = true;

    protected $fillable = [
        'student_session_id',
        'subject_timetable_id',
        'attendence_type_id',
        'date',
        'remark',
    ];

    public function type(): BelongsTo
    {
        return $this->belongsTo(AttendenceType::class, 'attendence_type_id');
    }
}
