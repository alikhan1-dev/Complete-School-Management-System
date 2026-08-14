<?php

namespace App\Modules\FrontCms\Models;

use App\Modules\Shared\Models\BaseModel;

/**
 * CI table: front_cms_program_photos.
 */
class CmsProgramPhoto extends BaseModel
{
    protected $table = 'front_cms_program_photos';

    public $timestamps = true;

    protected $fillable = [
        'program_id',
        'media_gallery_id',
    ];
}
