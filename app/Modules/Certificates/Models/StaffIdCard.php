<?php

namespace App\Modules\Certificates\Models;

use App\Modules\Shared\Models\BaseModel;

/**
 * CI staff_id_card table — staff ID card design templates.
 * Note: table has no created_at / updated_at columns.
 */
class StaffIdCard extends BaseModel
{
    protected $table = 'staff_id_card';

    public $timestamps = false;

    protected $fillable = [
        'title',
        'school_name',
        'school_address',
        'background',
        'logo',
        'sign_image',
        'header_color',
        'enable_vertical_card',
        'enable_staff_role',
        'enable_staff_id',
        'enable_staff_department',
        'enable_designation',
        'enable_name',
        'enable_fathers_name',
        'enable_mothers_name',
        'enable_date_of_joining',
        'enable_permanent_address',
        'enable_staff_dob',
        'enable_staff_phone',
        'enable_staff_barcode',
        'status',
    ];
}
