<?php

namespace App\Modules\FrontCms\Models;

use App\Modules\Shared\Models\BaseModel;

/**
 * CI table: front_cms_menu_items.
 */
class CmsMenuItem extends BaseModel
{
    protected $table = 'front_cms_menu_items';

    public $timestamps = true;

    protected $fillable = [
        'menu_id',
        'menu',
        'page_id',
        'parent_id',
        'ext_url',
        'open_new_tab',
        'ext_url_link',
        'slug',
        'weight',
        'publish',
        'description',
        'is_active',
    ];
}
