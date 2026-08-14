<?php

namespace App\Modules\Chat\Models;

use App\Modules\Shared\Models\BaseModel;

/**
 * CI table: chat_connections.
 */
class ChatConnection extends BaseModel
{
    protected $table = 'chat_connections';

    protected $fillable = [
        'chat_user_one',
        'chat_user_two',
        'ip',
        'time',
    ];
}
