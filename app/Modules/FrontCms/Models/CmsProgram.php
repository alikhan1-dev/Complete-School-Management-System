<?php

namespace App\Modules\FrontCms\Models;

use App\Modules\Shared\Models\BaseModel;

/**
 * CI table: front_cms_programs.
 */
class CmsProgram extends BaseModel
{
    protected $table = 'front_cms_programs';

    public $timestamps = true;

    protected $fillable = [
        'type',
        'slug',
        'url',
        'title',
        'date',
        'event_start',
        'event_end',
        'event_venue',
        'description',
        'is_active',
        'meta_title',
        'meta_description',
        'meta_keyword',
        'feature_image',
        'publish_date',
        'publish',
        'sidebar',
    ];
}
