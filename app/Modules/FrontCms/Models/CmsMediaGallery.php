<?php

namespace App\Modules\FrontCms\Models;

use App\Modules\Shared\Models\BaseModel;

/**
 * CI table: front_cms_media_gallery.
 */
class CmsMediaGallery extends BaseModel
{
    protected $table = 'front_cms_media_gallery';

    public $timestamps = true;

    protected $fillable = [
        'image',
        'thumb_path',
        'dir_path',
        'img_name',
        'thumb_name',
        'file_type',
        'file_size',
        'vid_url',
        'vid_title',
    ];
}
