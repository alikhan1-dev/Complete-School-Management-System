<?php

namespace App\Modules\LessonPlan\Models;

use App\Modules\Shared\Models\BaseModel;

/**
 * CI table: lesson.
 */
class Lesson extends BaseModel
{
    protected $table = 'lesson';

    public $timestamps = true;

    protected $fillable = [
        'session_id',
        'subject_group_subject_id',
        'subject_group_class_sections_id',
        'name',
    ];
}
