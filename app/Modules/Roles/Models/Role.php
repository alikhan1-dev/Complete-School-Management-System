<?php

namespace App\Modules\Roles\Models;

use App\Modules\Shared\Models\BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Role extends BaseModel
{
    protected $table = 'roles';

    protected $fillable = [
        'name',
        'slug',
        'is_active',
        'is_system',
        'is_superadmin',
    ];

    public function permissions(): HasMany
    {
        return $this->hasMany(RolePermission::class, 'role_id');
    }

    public function staff(): BelongsToMany
    {
        return $this->belongsToMany(
            \App\Modules\Staff\Models\Staff::class,
            'staff_roles',
            'role_id',
            'staff_id'
        );
    }
}
