<?php

namespace App\Modules\Staff\Models;

use App\Modules\Shared\Models\BaseModel;

class Department extends BaseModel
{
    protected $table = 'department';

    protected $fillable = [
        'department_name',
        'is_active',
    ];
}
