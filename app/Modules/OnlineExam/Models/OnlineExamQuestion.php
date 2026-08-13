<?php

namespace App\Modules\OnlineExam\Models;

use App\Modules\Shared\Models\BaseModel;

/**
 * CI onlineexam_questions — questions attached to an online exam.
 */
class OnlineExamQuestion extends BaseModel
{
    protected $table = 'onlineexam_questions';

    public $timestamps = true;

    protected $fillable = [
        'question_id',
        'onlineexam_id',
        'session_id',
        'marks',
        'neg_marks',
        'is_active',
    ];
}
