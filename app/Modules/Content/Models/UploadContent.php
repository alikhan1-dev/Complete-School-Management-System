<?php

namespace App\Modules\Content\Models;

use App\Modules\Shared\Models\BaseModel;

/**
 * CI table: upload_contents.
 */
class UploadContent extends BaseModel
{
    protected $table = 'upload_contents';

    public $timestamps = true;

    protected $fillable = [
        'content_type_id',
        'image',
        'thumb_path',
        'dir_path',
        'real_name',
        'img_name',
        'thumb_name',
        'file_type',
        'mime_type',
        'file_size',
        'vid_url',
        'vid_title',
        'upload_by',
        'created_at',
    ];
}
