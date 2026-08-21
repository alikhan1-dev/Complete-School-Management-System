<?php

namespace App\Modules\Students\Models;

use App\Modules\Shared\Models\BaseModel;

class AlumniStudent extends BaseModel
{
    protected $table = 'alumni_students';

    public $timestamps = true;

    protected $fillable = [
        'student_id',
        'current_email',
        'current_phone',
        'occupation',
        'address',
        'photo',
    ];
}
