<?php

namespace App\Modules\Roles\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Roles\Models\PermissionCategory;
use App\Modules\Roles\Models\Role;
use App\Modules\Roles\Models\RolePermission;
use App\Modules\Roles\Requests\UpdateRolePermissionsRequest;
use App\Modules\Shared\Services\DataTableResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class RoleController extends Controller
{
    public function index(): View
    {
        return view('shared::layouts.admin', [
            'title' => 'Roles',
            'contentView' => 'roles::admin.index',
        ]);
    }

    public function datatable(Request $request): JsonResponse
    {
        $draw = (int) $request->input('draw', 1);
        $roles = Role::query()->orderBy('id')->get()->map(function (Role $role) {
            return [
                $role->id,
                $role->name,
                $role->is_superadmin ? 'Yes' : 'No',
                $role->is_active ? 'Active' : 'Inactive',
                '<a href="'.route('roles.permissions', $role).'" class="btn btn-xs btn-primary">Permissions</a>',
            ];
        })->all();

        return DataTableResponse::make($draw, count($roles), count($roles), $roles);
    }

    public function permissions(Role $role): View
    {
        $categories = PermissionCategory::query()->with('group')->orderBy('id')->get();
        $assigned = RolePermission::query()
            ->where('role_id', $role->id)
            ->get()
            ->keyBy('perm_cat_id');

        return view('shared::layouts.admin', [
            'title' => 'Role Permissions',
            'contentView' => 'roles::admin.permissions',
            'role' => $role,
            'categories' => $categories,
            'assigned' => $assigned,
        ]);
    }

    public function updatePermissions(UpdateRolePermissionsRequest $request, Role $role): RedirectResponse
    {
        $data = $request->input('permissions', []);

        foreach ($data as $permCatId => $abilities) {
            RolePermission::query()->updateOrCreate(
                [
                    'role_id' => $role->id,
                    'perm_cat_id' => (int) $permCatId,
                ],
                [
                    'can_view' => isset($abilities['can_view']) ? 1 : 0,
                    'can_add' => isset($abilities['can_add']) ? 1 : 0,
                    'can_edit' => isset($abilities['can_edit']) ? 1 : 0,
                    'can_delete' => isset($abilities['can_delete']) ? 1 : 0,
                ]
            );
        }

        return redirect()->route('roles.permissions', $role)->with('success', 'Permissions updated.');
    }
}
