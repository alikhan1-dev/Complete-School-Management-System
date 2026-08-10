<?php

namespace App\Modules\Roles\Models;

use App\Modules\Shared\Models\BaseModel;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PermissionGroup extends BaseModel
{
    protected $table = 'permission_group';

    protected $fillable = [
        'name',
        'short_code',
        'is_active',
        'system',
    ];

    public function categories(): HasMany
    {
        return $this->hasMany(PermissionCategory::class, 'perm_group_id');
    }
}
