<?php

namespace App\Modules\Roles\Models;

use App\Modules\Shared\Models\BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RolePermission extends BaseModel
{
    protected $table = 'roles_permissions';

    protected $fillable = [
        'role_id',
        'perm_cat_id',
        'can_view',
        'can_add',
        'can_edit',
        'can_delete',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(PermissionCategory::class, 'perm_cat_id');
    }

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class, 'role_id');
    }
}
