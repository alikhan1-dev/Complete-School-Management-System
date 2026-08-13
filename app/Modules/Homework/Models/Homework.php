<?php

namespace App\Modules\Homework\Models;

use App\Modules\Shared\Models\BaseModel;

/**
 * CI homework table — teacher-assigned homework rows.
 */
class Homework extends BaseModel
{
    protected $table = 'homework';

    public $timestamps = true;

    protected $fillable = [
        'class_id',
        'section_id',
        'session_id',
        'staff_id',
        'subject_group_subject_id',
        'subject_id',
        'homework_date',
        'submit_date',
        'marks',
        'description',
        'create_date',
        'evaluation_date',
        'document',
        'created_by',
        'evaluated_by',
    ];
}
