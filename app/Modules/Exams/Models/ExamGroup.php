<?php

namespace App\Modules\Exams\Models;

use App\Modules\Shared\Models\BaseModel;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ExamGroup extends BaseModel
{
    protected $table = 'exam_groups';

    public $timestamps = true;

    protected $fillable = [
        'name',
        'exam_type',
        'description',
        'is_active',
    ];

    public function exams(): HasMany
    {
        return $this->hasMany(ExamGroupExam::class, 'exam_group_id');
    }
}
