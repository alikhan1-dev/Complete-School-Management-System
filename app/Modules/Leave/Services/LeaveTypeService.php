<?php

namespace App\Modules\Leave\Services;

use App\Modules\Leave\Models\LeaveType;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

/**
 * CI Leavetypes_model — leave type master CRUD.
 */
class LeaveTypeService
{
    /**
     * @return Collection<int, LeaveType>
     */
    public function listAll(): Collection
    {
        return LeaveType::query()->orderBy('id')->get();
    }

    /**
     * CI Staff_model::getLeaveType() without id — active only.
     *
     * @return Collection<int, LeaveType>
     */
    public function listActive(): Collection
    {
        return LeaveType::query()
            ->where('is_active', 'yes')
            ->orderBy('id')
            ->get();
    }

    public function find(int $id): LeaveType
    {
        return LeaveType::query()->findOrFail($id);
    }

    /**
     * @param  array{type: string}  $data
     */
    public function create(array $data): LeaveType
    {
        $this->assertUniqueType($data['type']);

        return LeaveType::query()->create([
            'type' => $data['type'],
            'is_active' => 'yes',
        ]);
    }

    /**
     * @param  array{type: string}  $data
     */
    public function update(LeaveType $type, array $data): LeaveType
    {
        $this->assertUniqueType($data['type'], (int) $type->id);
        $type->type = $data['type'];
        $type->is_active = 'yes';
        $type->save();

        return $type;
    }

    public function delete(LeaveType $type): void
    {
        $type->delete();
    }

    protected function assertUniqueType(string $type, ?int $ignoreId = null): void
    {
        $q = LeaveType::query()->where('type', $type);
        if ($ignoreId !== null) {
            $q->where('id', '!=', $ignoreId);
        }
        if ($q->exists()) {
            throw ValidationException::withMessages([
                'type' => 'Record already exists',
            ]);
        }
    }
}
