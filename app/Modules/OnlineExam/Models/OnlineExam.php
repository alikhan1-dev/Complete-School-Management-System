<?php

namespace App\Modules\OnlineExam\Models;

use App\Modules\Shared\Models\BaseModel;

/**
 * CI onlineexam table — online examination definition.
 */
class OnlineExam extends BaseModel
{
    protected $table = 'onlineexam';

    public $timestamps = true;

    protected $fillable = [
        'session_id',
        'exam',
        'attempt',
        'exam_from',
        'exam_to',
        'is_quiz',
        'auto_publish_date',
        'time_from',
        'time_to',
        'duration',
        'passing_percentage',
        'description',
        'publish_result',
        'answer_word_count',
        'is_active',
        'is_marks_display',
        'is_neg_marking',
        'is_random_question',
        'is_rank_generated',
        'publish_exam_notification',
        'publish_result_notification',
    ];
}
