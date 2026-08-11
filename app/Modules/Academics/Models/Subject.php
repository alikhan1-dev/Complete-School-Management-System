<?php

namespace App\Modules\Academics\Models;

use App\Modules\Shared\Models\BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Subject extends BaseModel
{
    protected $table = 'subjects';

    protected $fillable = [
        'name',
        'code',
        'type',
        'is_active',
    ];

    public function subjectGroups(): BelongsToMany
    {
        return $this->belongsToMany(SubjectGroup::class, 'subject_group_subjects', 'subject_id', 'subject_group_id')
            ->withPivot(['id', 'session_id']);
    }
}
