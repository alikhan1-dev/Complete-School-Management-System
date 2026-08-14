<?php

namespace App\Modules\FrontOffice\Models;

use App\Modules\Shared\Models\BaseModel;

/**
 * CI table: visitors_book.
 */
class Visitor extends BaseModel
{
    protected $table = 'visitors_book';

    public $timestamps = true;

    protected $fillable = [
        'staff_id',
        'student_session_id',
        'source',
        'purpose',
        'name',
        'email',
        'contact',
        'id_proof',
        'no_of_people',
        'date',
        'in_time',
        'out_time',
        'note',
        'image',
        'meeting_with',
    ];
}
