<?php

namespace App\Modules\Content\Models;

use App\Modules\Shared\Models\BaseModel;

/**
 * CI table: share_contents.
 */
class ShareContent extends BaseModel
{
    protected $table = 'share_contents';

    public $timestamps = true;

    protected $fillable = [
        'send_to',
        'title',
        'share_date',
        'valid_upto',
        'description',
        'created_by',
        'created_at',
    ];
}
