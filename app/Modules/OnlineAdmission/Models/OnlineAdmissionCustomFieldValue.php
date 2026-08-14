<?php

namespace App\Modules\OnlineAdmission\Models;

use App\Modules\Shared\Models\BaseModel;

/**
 * CI table: online_admission_custom_field_value.
 */
class OnlineAdmissionCustomFieldValue extends BaseModel
{
    protected $table = 'online_admission_custom_field_value';

    public $timestamps = true;

    protected $fillable = [
        'belong_table_id',
        'custom_field_id',
        'field_value',
    ];
}
