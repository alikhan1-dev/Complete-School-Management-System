<?php

namespace App\Modules\Certificates\Models;

use App\Modules\Shared\Models\BaseModel;

/**
 * CI certificates table — student certificate templates (created_for = 2).
 */
class Certificate extends BaseModel
{
    protected $table = 'certificates';

    public $timestamps = true;

    protected $fillable = [
        'certificate_name',
        'certificate_text',
        'left_header',
        'center_header',
        'right_header',
        'left_footer',
        'right_footer',
        'center_footer',
        'background_image',
        'created_for',
        'status',
        'header_height',
        'content_height',
        'footer_height',
        'content_width',
        'enable_student_image',
        'enable_image_height',
    ];
}
