<?php

namespace App\Modules\Library\Models;

use App\Modules\Shared\Models\BaseModel;

/**
 * CI libarary_members — intentional table-name typo preserved.
 */
class LibraryMember extends BaseModel
{
    protected $table = 'libarary_members';

    public $timestamps = true;

    protected $fillable = [
        'library_card_no',
        'member_type',
        'member_id',
        'is_active',
    ];
}
