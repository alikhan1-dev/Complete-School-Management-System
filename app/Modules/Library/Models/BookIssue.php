<?php

namespace App\Modules\Library\Models;

use App\Modules\Shared\Models\BaseModel;

/**
 * CI book_issues — issue/return rows for libarary_members.
 */
class BookIssue extends BaseModel
{
    protected $table = 'book_issues';

    public $timestamps = true;

    protected $fillable = [
        'book_id',
        'member_id',
        'duereturn_date',
        'return_date',
        'issue_date',
        'is_returned',
        'is_active',
    ];
}
