<?php

namespace App\Modules\Academics\Models;

use App\Modules\Shared\Models\BaseModel;

class MarkDivision extends BaseModel
{
    protected $table = 'mark_divisions';

    protected $fillable = [
        'name',
        'percentage_from',
        'percentage_to',
        'is_active',
    ];
}
