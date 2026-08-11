<?php

namespace App\Modules\Academics\Models;

use App\Modules\Shared\Models\BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SubjectGroup extends BaseModel
{
    protected $table = 'subject_groups';

    protected $fillable = [
        'name',
        'description',
        'session_id',
    ];

    public function session(): BelongsTo
    {
        return $this->belongsTo(AcademicSession::class, 'session_id');
    }

    public function subjects(): BelongsToMany
    {
        return $this->belongsToMany(Subject::class, 'subject_group_subjects', 'subject_group_id', 'subject_id')
            ->withPivot(['id', 'session_id']);
    }

    public function classSections(): BelongsToMany
    {
        return $this->belongsToMany(ClassSection::class, 'subject_group_class_sections', 'subject_group_id', 'class_section_id')
            ->withPivot(['id', 'session_id', 'description', 'is_active']);
    }

    public function subjectLinks(): HasMany
    {
        return $this->hasMany(SubjectGroupSubject::class, 'subject_group_id');
    }

    public function classSectionLinks(): HasMany
    {
        return $this->hasMany(SubjectGroupClassSection::class, 'subject_group_id');
    }
}
