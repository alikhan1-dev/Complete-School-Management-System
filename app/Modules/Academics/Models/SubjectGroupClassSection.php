<?php

namespace App\Modules\Academics\Models;

use App\Modules\Shared\Models\BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SubjectGroupClassSection extends BaseModel
{
    protected $table = 'subject_group_class_sections';

    protected $fillable = [
        'subject_group_id',
        'class_section_id',
        'session_id',
        'description',
        'is_active',
    ];

    public function subjectGroup(): BelongsTo
    {
        return $this->belongsTo(SubjectGroup::class, 'subject_group_id');
    }

    public function classSection(): BelongsTo
    {
        return $this->belongsTo(ClassSection::class, 'class_section_id');
    }
}
