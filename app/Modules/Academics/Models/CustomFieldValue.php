<?php

namespace App\Modules\Academics\Models;

use App\Modules\Shared\Models\BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustomFieldValue extends BaseModel
{
    protected $table = 'custom_field_values';

    protected $fillable = [
        'belong_table_id',
        'custom_field_id',
        'field_value',
    ];

    public function field(): BelongsTo
    {
        return $this->belongsTo(CustomField::class, 'custom_field_id');
    }
}
