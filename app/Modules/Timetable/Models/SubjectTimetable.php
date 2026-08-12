<?php

namespace App\Modules\Timetable\Models;

use App\Modules\Shared\Models\BaseModel;

/**
 * CI table: subject_timetable.
 */
class SubjectTimetable extends BaseModel
{
    protected $table = 'subject_timetable';

    public $timestamps = true;

    protected $fillable = [
        'session_id',
        'class_id',
        'section_id',
        'subject_group_id',
        'subject_group_subject_id',
        'staff_id',
        'day',
        'time_from',
        'time_to',
        'start_time',
        'end_time',
        'room_no',
    ];
}
