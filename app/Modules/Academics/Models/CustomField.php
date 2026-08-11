<?php

namespace App\Modules\Academics\Models;

use App\Modules\Shared\Models\BaseModel;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CustomField extends BaseModel
{
    protected $table = 'custom_fields';

    protected $fillable = [
        'name',
        'belong_to',
        'type',
        'bs_column',
        'validation',
        'field_values',
        'show_table',
        'visible_on_table',
        'weight',
        'is_active',
    ];

    public function values(): HasMany
    {
        return $this->hasMany(CustomFieldValue::class, 'custom_field_id');
    }
}
