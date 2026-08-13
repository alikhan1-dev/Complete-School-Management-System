<?php

namespace App\Modules\Exams\Models;

use App\Modules\Shared\Models\BaseModel;

class TemplateMarksheet extends BaseModel
{
    protected $table = 'template_marksheets';

    public $timestamps = true;

    protected $fillable = [
        'header_image',
        'template',
        'heading',
        'title',
        'left_logo',
        'right_logo',
        'exam_name',
        'school_name',
        'exam_center',
        'left_sign',
        'middle_sign',
        'right_sign',
        'exam_session',
        'is_name',
        'is_father_name',
        'is_mother_name',
        'is_dob',
        'is_admission_no',
        'is_roll_no',
        'is_photo',
        'is_division',
        'is_rank',
        'is_customfield',
        'background_img',
        'date',
        'is_class',
        'is_teacher_remark',
        'is_section',
        'content',
        'content_footer',
    ];
}
