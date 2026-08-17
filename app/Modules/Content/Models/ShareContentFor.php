<?php

namespace App\Modules\Content\Models;

use App\Modules\Shared\Models\BaseModel;

/**
 * CI table: share_content_for.
 */
class ShareContentFor extends BaseModel
{
    protected $table = 'share_content_for';

    public $timestamps = true;

    protected $fillable = [
        'group_id',
        'student_id',
        'user_parent_id',
        'staff_id',
        'class_section_id',
        'share_content_id',
    ];
}
