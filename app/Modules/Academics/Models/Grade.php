<?php

namespace App\Modules\Academics\Models;

use App\Modules\Shared\Models\BaseModel;

class Grade extends BaseModel
{
    protected $table = 'grades';

    protected $fillable = [
        'exam_type',
        'name',
        'point',
        'mark_from',
        'mark_upto',
        'description',
        'is_active',
    ];
}
