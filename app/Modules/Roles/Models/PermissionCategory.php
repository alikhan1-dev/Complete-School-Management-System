<?php

namespace App\Modules\Roles\Models;

use App\Modules\Shared\Models\BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PermissionCategory extends BaseModel
{
    protected $table = 'permission_category';

    protected $fillable = [
        'perm_group_id',
        'name',
        'short_code',
        'enable_view',
        'enable_add',
        'enable_edit',
        'enable_delete',
    ];

    public function group(): BelongsTo
    {
        return $this->belongsTo(PermissionGroup::class, 'perm_group_id');
    }

    public function rolePermissions(): HasMany
    {
        return $this->hasMany(RolePermission::class, 'perm_cat_id');
    }
}
