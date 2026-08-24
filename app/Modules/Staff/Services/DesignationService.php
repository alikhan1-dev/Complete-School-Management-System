<?php

namespace App\Modules\Staff\Services;

use App\Modules\Staff\Models\StaffDesignation;
use Illuminate\Support\Collection;

/**
 * CI designation_model — staff designation master CRUD.
 */
class DesignationService
{
    /**
     * CI get() without id — active designations only.
     *
     * @return Collection<int, StaffDesignation>
     */
    public function listActive(): Collection
    {
        return StaffDesignation::query()
            ->where('is_active', 'yes')
            ->orderBy('id')
            ->get();
    }

    public function find(int $id): StaffDesignation
    {
        return StaffDesignation::query()->findOrFail($id);
    }

    public function nameExists(string $name, int $excludeId = 0): bool
    {
        $query = StaffDesignation::query()->where('designation', $name);
        if ($excludeId > 0) {
            $query->where('id', '!=', $excludeId);
        }

        return $query->exists();
    }

    public function create(string $name): StaffDesignation
    {
        return StaffDesignation::query()->create([
            'designation' => $name,
            'is_active' => 'yes',
        ]);
    }

    public function update(StaffDesignation $row, string $name): StaffDesignation
    {
        $row->designation = $name;
        $row->is_active = 'yes';
        $row->save();

        return $row;
    }

    public function delete(StaffDesignation $row): void
    {
        $row->delete();
    }
}
