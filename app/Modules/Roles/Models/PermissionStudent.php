<?php

namespace App\Modules\Roles\Models;

use App\Modules\Shared\Models\BaseModel;

class PermissionStudent extends BaseModel
{
    protected $table = 'permission_student';

    protected $fillable = [
        'name',
        'short_code',
        'system',
        'student',
        'parent',
        'group_id',
    ];
}
