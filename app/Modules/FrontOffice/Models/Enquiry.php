<?php

namespace App\Modules\FrontOffice\Models;

use App\Modules\Shared\Models\BaseModel;

/**
 * CI table: enquiry.
 */
class Enquiry extends BaseModel
{
    protected $table = 'enquiry';

    public $timestamps = true;

    protected $fillable = [
        'name',
        'contact',
        'address',
        'reference',
        'date',
        'description',
        'follow_up_date',
        'note',
        'source',
        'email',
        'assigned',
        'class_id',
        'no_of_child',
        'status',
        'created_by',
    ];
}
