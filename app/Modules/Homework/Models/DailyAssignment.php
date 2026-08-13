<?php

namespace App\Modules\Homework\Models;

use App\Modules\Shared\Models\BaseModel;

/**
 * CI daily_assignment — student-authored daily work rows.
 */
class DailyAssignment extends BaseModel
{
    protected $table = 'daily_assignment';

    public $timestamps = true;

    protected $fillable = [
        'student_session_id',
        'subject_group_subject_id',
        'title',
        'description',
        'attachment',
        'evaluated_by',
        'date',
        'evaluation_date',
        'remark',
    ];
}
