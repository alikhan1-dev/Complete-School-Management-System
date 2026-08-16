<?php

namespace App\Modules\Settings\Models;

use App\Modules\Shared\Models\BaseModel;

/**
 * CI table google_drive_setting (Student_model::getgoogledrivepickersetting / savegoogledrive).
 */
class GoogleDriveSetting extends BaseModel
{
    protected $table = 'google_drive_setting';

    public $timestamps = false;

    protected $guarded = [];
}
