<?php

namespace App\Modules\FrontOffice\Models;

use App\Modules\Shared\Models\BaseModel;

/**
 * CI table: complaint.
 */
class Complaint extends BaseModel
{
    protected $table = 'complaint';

    public $timestamps = true;

    protected $fillable = [
        'complaint_type',
        'source',
        'name',
        'contact',
        'email',
        'date',
        'description',
        'action_taken',
        'assigned',
        'note',
        'image',
    ];
}
