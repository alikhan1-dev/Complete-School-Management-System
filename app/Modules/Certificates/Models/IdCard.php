<?php

namespace App\Modules\Certificates\Models;

use App\Modules\Shared\Models\BaseModel;

/**
 * CI id_card table — student ID card design templates.
 */
class IdCard extends BaseModel
{
    protected $table = 'id_card';

    public $timestamps = true;

    protected $fillable = [
        'title',
        'school_name',
        'school_address',
        'background',
        'logo',
        'sign_image',
        'enable_vertical_card',
        'header_color',
        'enable_admission_no',
        'enable_student_name',
        'enable_class',
        'enable_fathers_name',
        'enable_mothers_name',
        'enable_address',
        'enable_phone',
        'enable_dob',
        'enable_blood_group',
        'enable_student_barcode',
        'enable_student_rollno',
        'enable_student_house_name',
        'status',
    ];
}
