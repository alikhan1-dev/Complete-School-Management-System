<?php

namespace App\Modules\Chat\Models;

use App\Modules\Shared\Models\BaseModel;

/**
 * CI table: chat_messages.
 */
class ChatMessage extends BaseModel
{
    protected $table = 'chat_messages';

    public $timestamps = false;

    protected $fillable = [
        'message',
        'chat_user_id',
        'ip',
        'time',
        'is_first',
        'is_read',
        'chat_connection_id',
        'created_at',
        'updated_at',
    ];
}
