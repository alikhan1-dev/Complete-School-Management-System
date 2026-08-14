<?php

namespace App\Modules\LessonPlan\Models;

use App\Modules\Shared\Models\BaseModel;

/**
 * CI table: topic.
 * status: 0=incomplete, 1=complete
 */
class Topic extends BaseModel
{
    protected $table = 'topic';

    public $timestamps = true;

    protected $fillable = [
        'session_id',
        'lesson_id',
        'name',
        'status',
        'complete_date',
    ];
}
