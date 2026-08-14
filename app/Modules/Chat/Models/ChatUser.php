<?php

namespace App\Modules\Chat\Models;

use App\Modules\Shared\Models\BaseModel;

/**
 * CI table: chat_users.
 */
class ChatUser extends BaseModel
{
    protected $table = 'chat_users';

    protected $fillable = [
        'user_type',
        'staff_id',
        'student_id',
        'create_staff_id',
        'create_student_id',
        'is_active',
    ];
}
