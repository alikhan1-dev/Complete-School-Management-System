<?php

namespace App\Modules\Exams\Models;

use App\Modules\Academics\Models\Subject;
use App\Modules\Shared\Models\BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * CI table: exam_group_class_batch_exam_subjects.
 */
class ExamGroupExamSubject extends BaseModel
{
    protected $table = 'exam_group_class_batch_exam_subjects';

    public $timestamps = true;

    protected $fillable = [
        'exam_group_class_batch_exams_id',
        'subject_id',
        'date_from',
        'time_from',
        'duration',
        'room_no',
        'max_marks',
        'min_marks',
        'credit_hours',
        'date_to',
        'is_active',
    ];

    public function exam(): BelongsTo
    {
        return $this->belongsTo(ExamGroupExam::class, 'exam_group_class_batch_exams_id');
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class, 'subject_id');
    }
}
