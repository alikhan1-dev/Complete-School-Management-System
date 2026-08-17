<?php

namespace App\Modules\Settings\Services;

use App\Modules\Roles\Models\PermissionGroup;
use App\Modules\Roles\Models\PermissionStudent;
use Illuminate\Support\Facades\DB;

/**
 * CI admin/Module + Module_model (system=0 rows only).
 */
class SchoolModuleSettingService
{
    /**
     * @return list<array<string, mixed>>
     */
    public function systemModules(): array
    {
        return PermissionGroup::query()
            ->where('system', 0)
            ->orderBy('id')
            ->get()
            ->map(fn (PermissionGroup $row) => $row->toArray())
            ->all();
    }

    /**
     * CI getStudentPermission / getParentPermission both read permission_student.
     *
     * @return list<array<string, mixed>>
     */
    public function studentParentModules(): array
    {
        return PermissionStudent::query()
            ->where('system', 0)
            ->orderBy('id')
            ->get()
            ->map(fn (PermissionStudent $row) => $row->toArray())
            ->all();
    }

    /**
     * CI Module_model::changeStatus — group flag plus student/parent cascade.
     */
    public function changeSystemStatus(int $id, int $status): void
    {
        DB::transaction(function () use ($id, $status) {
            PermissionGroup::query()->where('id', $id)->update(['is_active' => $status]);
            PermissionStudent::query()->where('group_id', $id)->update([
                'student' => $status,
                'parent' => $status,
            ]);
        });
    }

    /**
     * CI Module_model::changeStudentStatus — only student|parent columns.
     */
    public function changeStudentParentStatus(int $id, string $role, int $status): void
    {
        $column = $role === 'parent' ? 'parent' : 'student';

        PermissionStudent::query()->where('id', $id)->update([
            $column => $status,
        ]);
    }
}
