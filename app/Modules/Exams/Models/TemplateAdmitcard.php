<?php

namespace App\Modules\Exams\Models;

use App\Modules\Shared\Models\BaseModel;

class TemplateAdmitcard extends BaseModel
{
    protected $table = 'template_admitcards';

    public $timestamps = true;

    protected $fillable = [
        'template',
        'heading',
        'title',
        'left_logo',
        'right_logo',
        'exam_name',
        'school_name',
        'exam_center',
        'sign',
        'background_img',
        'is_name',
        'is_father_name',
        'is_mother_name',
        'is_dob',
        'is_admission_no',
        'is_roll_no',
        'is_address',
        'is_gender',
        'is_photo',
        'is_class',
        'is_section',
        'is_active',
        'content_footer',
    ];
}
