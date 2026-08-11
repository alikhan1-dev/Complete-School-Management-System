<?php

namespace App\Modules\Academics\Models;

use App\Modules\Shared\Models\BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Pivot class_sections — id is used by subject groups as class_section_id.
 */
class ClassSection extends BaseModel
{
    protected $table = 'class_sections';

    protected $fillable = [
        'class_id',
        'section_id',
        'is_active',
    ];

    public function schoolClass(): BelongsTo
    {
        return $this->belongsTo(SchoolClass::class, 'class_id');
    }

    public function section(): BelongsTo
    {
        return $this->belongsTo(Section::class, 'section_id');
    }
}
