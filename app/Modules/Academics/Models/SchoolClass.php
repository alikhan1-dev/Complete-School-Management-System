<?php

namespace App\Modules\Academics\Models;

use App\Modules\Shared\Models\BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SchoolClass extends BaseModel
{
    protected $table = 'classes';

    protected $fillable = [
        'class',
        'is_active',
    ];

    public function classSections(): HasMany
    {
        return $this->hasMany(ClassSection::class, 'class_id');
    }

    public function sections(): BelongsToMany
    {
        return $this->belongsToMany(Section::class, 'class_sections', 'class_id', 'section_id')
            ->withPivot(['id', 'is_active']);
    }
}
