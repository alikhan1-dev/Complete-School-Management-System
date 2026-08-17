<?php

namespace App\Modules\Content\Models;

use App\Modules\Shared\Models\BaseModel;

/**
 * CI table: content_types.
 */
class ContentType extends BaseModel
{
    protected $table = 'content_types';

    public $timestamps = true;

    protected $fillable = [
        'name',
        'description',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'integer',
    ];
}
