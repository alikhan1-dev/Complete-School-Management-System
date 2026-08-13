<?php

namespace App\Modules\Library\Models;

use App\Modules\Shared\Models\BaseModel;

/**
 * CI books — library catalog rows.
 */
class Book extends BaseModel
{
    protected $table = 'books';

    public $timestamps = true;

    protected $fillable = [
        'book_title',
        'book_no',
        'isbn_no',
        'subject',
        'rack_no',
        'publish',
        'author',
        'qty',
        'perunitcost',
        'postdate',
        'description',
        'available',
        'is_active',
    ];
}
