<?php

namespace App\Modules\LessonPlan\Models;

use App\Modules\Shared\Models\BaseModel;

/**
 * CI table: subject_syllabus — weekly lesson plan entries.
 */
class SubjectSyllabus extends BaseModel
{
    protected $table = 'subject_syllabus';

    public $timestamps = true;

    protected $fillable = [
        'topic_id',
        'session_id',
        'created_by',
        'created_for',
        'date',
        'time_from',
        'time_to',
        'presentation',
        'attachment',
        'lacture_youtube_url',
        'lacture_video',
        'sub_topic',
        'teaching_method',
        'general_objectives',
        'previous_knowledge',
        'comprehensive_questions',
        'status',
    ];
}
