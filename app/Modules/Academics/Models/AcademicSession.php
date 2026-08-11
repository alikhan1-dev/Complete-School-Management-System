<?php

namespace App\Modules\Academics\Models;

use App\Modules\Shared\Models\BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AcademicSession extends BaseModel
{
    protected $table = 'sessions';

    protected $fillable = [
        'session',
        'is_active',
    ];

    public function subjectGroups(): HasMany
    {
        return $this->hasMany(SubjectGroup::class, 'session_id');
    }
}
