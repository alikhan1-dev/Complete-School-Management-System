<?php

namespace App\Modules\Auth\Models;

use App\Modules\Shared\Casts\YesNoBoolean;
use App\Modules\Shared\Models\BaseModel;
use Illuminate\Auth\Authenticatable;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;

/**
 * Portal login account (students / parents / guests) — table `users`.
 */
class PortalUser extends BaseModel implements AuthenticatableContract
{
    use Authenticatable;

    protected $table = 'users';

    protected $fillable = [
        'user_id',
        'username',
        'password',
        'childs',
        'role',
        'lang_id',
        'currency_id',
        'verification_code',
        'login_token',
        'is_active',
    ];

    protected $hidden = [
        'password',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => YesNoBoolean::class,
        ];
    }

    public function getAuthPassword(): string
    {
        return (string) $this->password;
    }

    public function getAuthIdentifierName(): string
    {
        return 'id';
    }
}
