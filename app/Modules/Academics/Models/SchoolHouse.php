<?php

namespace App\Modules\Academics\Models;

use App\Modules\Shared\Models\BaseModel;

class SchoolHouse extends BaseModel
{
    protected $table = 'school_houses';

    protected $fillable = [
        'house_name',
        'description',
        'is_active',
    ];
}
