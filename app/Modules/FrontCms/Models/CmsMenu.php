<?php

namespace App\Modules\FrontCms\Models;

use App\Modules\Shared\Models\BaseModel;

/**
 * CI table: front_cms_menus.
 */
class CmsMenu extends BaseModel
{
    protected $table = 'front_cms_menus';

    public $timestamps = true;

    protected $fillable = [
        'menu',
        'slug',
        'description',
        'open_new_tab',
        'ext_url',
        'ext_url_link',
        'publish',
        'content_type',
        'is_active',
    ];
}
