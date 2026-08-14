<?php

namespace App\Modules\FrontCms\Models;

use App\Modules\Shared\Models\BaseModel;

/**
 * CI table: front_cms_page_contents.
 */
class CmsPageContent extends BaseModel
{
    protected $table = 'front_cms_page_contents';

    public $timestamps = true;

    protected $fillable = [
        'page_id',
        'content_type',
    ];
}
