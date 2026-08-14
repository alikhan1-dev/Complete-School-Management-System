<?php

namespace App\Modules\FrontCms\Models;

use App\Modules\Shared\Models\BaseModel;

/**
 * CI table: front_cms_settings.
 */
class FrontCmsSetting extends BaseModel
{
    protected $table = 'front_cms_settings';

    public $timestamps = true;

    protected $guarded = [];
}
