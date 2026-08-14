<?php

namespace App\Modules\LessonPlan\Models;

use App\Modules\Shared\Models\BaseModel;

/**
 * CI table: lesson_plan_forum.
 * type: staff | student
 */
class LessonPlanForum extends BaseModel
{
    protected $table = 'lesson_plan_forum';

    public $timestamps = false;

    protected $fillable = [
        'subject_syllabus_id',
        'type',
        'staff_id',
        'student_id',
        'message',
        'created_date',
    ];
}
