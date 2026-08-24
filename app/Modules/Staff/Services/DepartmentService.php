<?php

namespace App\Modules\Staff\Services;

use App\Modules\Staff\Models\Department;
use Illuminate\Support\Collection;

/**
 * CI department_model — department master CRUD.
 */
class DepartmentService
{
    /**
     * CI getDepartmentType() without id — all rows.
     *
     * @return Collection<int, Department>
     */
    public function all(): Collection
    {
        return Department::query()->orderBy('id')->get();
    }

    public function find(int $id): Department
    {
        return Department::query()->findOrFail($id);
    }

    public function nameExists(string $name, int $excludeId = 0): bool
    {
        $query = Department::query()->where('department_name', $name);
        if ($excludeId > 0) {
            $query->where('id', '!=', $excludeId);
        }

        return $query->exists();
    }

    public function create(string $name): Department
    {
        return Department::query()->create([
            'department_name' => $name,
            'is_active' => 'yes',
        ]);
    }

    public function update(Department $row, string $name): Department
    {
        $row->department_name = $name;
        $row->is_active = 'yes';
        $row->save();

        return $row;
    }

    public function delete(Department $row): void
    {
        $row->delete();
    }
}
