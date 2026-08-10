<?php

namespace App\Modules\Staff\Models;

use App\Modules\Roles\Models\Role;
use App\Modules\Shared\Models\BaseModel;
use Illuminate\Auth\Authenticatable;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Staff extends BaseModel implements AuthenticatableContract
{
    use Authenticatable;

    protected $table = 'staff';

    protected $fillable = [
        'employee_id',
        'name',
        'surname',
        'email',
        'password',
        'is_active',
        'image',
        'gender',
        'contact_no',
        'department',
        'designation',
        'lang_id',
        'currency_id',
        'user_id',
        'verification_code',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    public function getAuthPassword(): string
    {
        return (string) $this->password;
    }

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'staff_roles', 'staff_id', 'role_id')
            ->withPivot(['is_active']);
    }

    public function primaryRole(): ?Role
    {
        return $this->roles()->first();
    }

    public function isSuperAdmin(): bool
    {
        return $this->roles()->where('is_superadmin', 1)->exists()
            || $this->roles()->where('name', 'Super Admin')->exists();
    }
}
