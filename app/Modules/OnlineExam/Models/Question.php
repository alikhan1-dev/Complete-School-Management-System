<?php

namespace App\Modules\OnlineExam\Models;

use App\Modules\Shared\Models\BaseModel;

/**
 * CI questions table — question bank rows.
 */
class Question extends BaseModel
{
    protected $table = 'questions';

    public $timestamps = true;

    protected $fillable = [
        'staff_id',
        'subject_id',
        'question_type',
        'level',
        'class_id',
        'section_id',
        'class_section_id',
        'question',
        'opt_a',
        'opt_b',
        'opt_c',
        'opt_d',
        'opt_e',
        'correct',
        'descriptive_word_limit',
    ];
}
