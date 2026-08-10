<?php

namespace App\Modules\Roles\Services;

use App\Modules\Roles\Models\PermissionCategory;
use App\Modules\Roles\Models\PermissionStudent;
use App\Modules\Roles\Models\RolePermission;
use Illuminate\Support\Facades\Auth;

class PermissionService
{
    public function hasPrivilege(string $category, string $permission): bool
    {
        $staff = Auth::guard('staff')->user();

        if (! $staff) {
            return false;
        }

        if ($staff->isSuperAdmin()) {
            return true;
        }

        $role = $staff->primaryRole();

        if (! $role) {
            return false;
        }

        $permCat = PermissionCategory::query()
            ->where('short_code', trim($category))
            ->first();

        if (! $permCat) {
            return false;
        }

        $rolePerm = RolePermission::query()
            ->where('role_id', $role->id)
            ->where('perm_cat_id', $permCat->id)
            ->first();

        if (! $rolePerm) {
            return false;
        }

        return (bool) ($rolePerm->{$permission} ?? false);
    }

    public function studentParentHas(string $shortCode, string $role): bool
    {
        $column = $role === 'parent' ? 'parent' : 'student';

        $row = PermissionStudent::query()
            ->where('short_code', $shortCode)
            ->first();

        return $row ? (bool) $row->{$column} : false;
    }
}
