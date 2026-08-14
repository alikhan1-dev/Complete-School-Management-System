<?php

namespace App\Modules\FrontCms\Models;

use App\Modules\Shared\Models\BaseModel;

/**
 * CI table: front_cms_pages.
 */
class CmsPage extends BaseModel
{
    protected $table = 'front_cms_pages';

    public $timestamps = true;

    protected $fillable = [
        'title',
        'url',
        'type',
        'slug',
        'meta_title',
        'meta_description',
        'meta_keyword',
        'feature_image',
        'description',
        'sidebar',
    ];
}
