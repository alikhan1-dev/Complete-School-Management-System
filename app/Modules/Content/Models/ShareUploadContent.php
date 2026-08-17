<?php

namespace App\Modules\Content\Models;

use App\Modules\Shared\Models\BaseModel;

/**
 * CI table: share_upload_contents.
 */
class ShareUploadContent extends BaseModel
{
    protected $table = 'share_upload_contents';

    public $timestamps = true;

    protected $fillable = [
        'upload_content_id',
        'share_content_id',
    ];
}
