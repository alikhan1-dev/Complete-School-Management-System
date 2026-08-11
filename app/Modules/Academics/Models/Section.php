<?php

namespace App\Modules\Academics\Models;

use App\Modules\Shared\Models\BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Section extends BaseModel
{
    protected $table = 'sections';

    protected $fillable = [
        'section',
        'is_active',
    ];

    public function classSections(): HasMany
    {
        return $this->hasMany(ClassSection::class, 'section_id');
    }

    public function classes(): BelongsToMany
    {
        return $this->belongsToMany(SchoolClass::class, 'class_sections', 'section_id', 'class_id')
            ->withPivot(['id', 'is_active']);
    }
}
