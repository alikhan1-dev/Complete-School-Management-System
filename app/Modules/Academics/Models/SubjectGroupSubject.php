<?php

namespace App\Modules\Academics\Models;

use App\Modules\Shared\Models\BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SubjectGroupSubject extends BaseModel
{
    protected $table = 'subject_group_subjects';

    protected $fillable = [
        'subject_group_id',
        'subject_id',
        'session_id',
    ];

    public function subjectGroup(): BelongsTo
    {
        return $this->belongsTo(SubjectGroup::class, 'subject_group_id');
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class, 'subject_id');
    }
}
