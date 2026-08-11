<?php

namespace App\Modules\Students\Models;

use App\Modules\Shared\Models\BaseModel;

class Category extends BaseModel
{
    protected $table = 'categories';

    protected $fillable = [
        'category',
        'is_active',
    ];
}
