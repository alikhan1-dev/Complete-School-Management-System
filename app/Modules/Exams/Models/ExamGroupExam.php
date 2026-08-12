<?php

namespace App\Modules\Exams\Models;

use App\Modules\Academics\Models\AcademicSession;
use App\Modules\Shared\Models\BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * CI table: exam_group_class_batch_exams (exam within an exam group).
 */
class ExamGroupExam extends BaseModel
{
    protected $table = 'exam_group_class_batch_exams';

    public $timestamps = true;

    protected $fillable = [
        'exam',
        'passing_percentage',
        'session_id',
        'date_from',
        'date_to',
        'exam_group_id',
        'use_exam_roll_no',
        'is_publish',
        'is_rank_generated',
        'description',
        'is_active',
    ];

    public function group(): BelongsTo
    {
        return $this->belongsTo(ExamGroup::class, 'exam_group_id');
    }

    public function session(): BelongsTo
    {
        return $this->belongsTo(AcademicSession::class, 'session_id');
    }

    public function subjects(): HasMany
    {
        return $this->hasMany(ExamGroupExamSubject::class, 'exam_group_class_batch_exams_id');
    }
}
