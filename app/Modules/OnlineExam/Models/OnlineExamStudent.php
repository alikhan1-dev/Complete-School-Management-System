<?php

namespace App\Modules\OnlineExam\Models;

use App\Modules\Shared\Models\BaseModel;

/**
 * CI onlineexam_students — students assigned to an online exam.
 */
class OnlineExamStudent extends BaseModel
{
    protected $table = 'onlineexam_students';

    public $timestamps = true;

    protected $fillable = [
        'onlineexam_id',
        'student_session_id',
        'is_attempted',
        'rank',
        'quiz_attempted',
    ];
}
