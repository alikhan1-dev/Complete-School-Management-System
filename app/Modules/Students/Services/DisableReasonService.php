<?php

namespace App\Modules\Students\Services;

use App\Modules\Students\Models\DisableReason;
use Illuminate\Support\Collection;

/**
 * CI disable_reason_model — disable reason master CRUD.
 */
class DisableReasonService
{
    /**
     * @return Collection<int, DisableReason>
     */
    public function all(): Collection
    {
        return DisableReason::query()->orderBy('id')->get();
    }

    public function find(int $id): DisableReason
    {
        return DisableReason::query()->findOrFail($id);
    }

    public function create(string $reason): DisableReason
    {
        return DisableReason::query()->create([
            'reason' => $reason,
        ]);
    }

    public function update(DisableReason $row, string $reason): DisableReason
    {
        $row->reason = $reason;
        $row->save();

        return $row;
    }

    public function delete(DisableReason $row): void
    {
        $row->delete();
    }
}
