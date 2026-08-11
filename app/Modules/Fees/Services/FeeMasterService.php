<?php

namespace App\Modules\Fees\Services;

use App\Modules\Academics\Services\CurrentSessionResolver;
use App\Modules\Fees\Models\FeeGroupFeetype;
use App\Modules\Fees\Models\FeeSessionGroup;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * CI Feesessiongroup_model + Feegrouptype_model core (master list / add / edit / delete).
 * Cumulative fines deferred to a later slice.
 */
class FeeMasterService
{
    public function __construct(protected CurrentSessionResolver $currentSession)
    {
    }

    /**
     * @return Collection<int, FeeSessionGroup>
     */
    public function listForCurrentSession(bool $excludeSystem = true): Collection
    {
        $sessionId = $this->currentSession->id();

        $query = FeeSessionGroup::query()
            ->with(['feeGroup', 'feeTypes.feeType'])
            ->where('session_id', $sessionId)
            ->whereHas('feeGroup', function ($q) use ($excludeSystem) {
                $q->where('nature', '!=', 'custom');
                if ($excludeSystem) {
                    $q->where('is_system', 0);
                }
            })
            ->orderBy('fee_groups_id');

        return $query->get();
    }

    public function findRow(int $id): ?FeeGroupFeetype
    {
        return FeeGroupFeetype::query()->with(['feeType', 'feeGroup', 'sessionGroup'])->find($id);
    }

    public function ensureSessionGroup(int $feeGroupsId): FeeSessionGroup
    {
        $sessionId = $this->currentSession->id();
        if ($sessionId <= 0) {
            throw new \RuntimeException('Current academic session is not configured in sch_settings.');
        }

        $existing = FeeSessionGroup::query()
            ->where('fee_groups_id', $feeGroupsId)
            ->where('session_id', $sessionId)
            ->first();

        if ($existing) {
            return $existing;
        }

        return FeeSessionGroup::query()->create([
            'fee_groups_id' => $feeGroupsId,
            'session_id' => $sessionId,
            'is_active' => 'no',
        ]);
    }

    public function combinationExists(int $feeGroupsId, int $feetypeId, int $ignoreRowId = 0): bool
    {
        $sessionId = $this->currentSession->id();
        $sessionGroup = FeeSessionGroup::query()
            ->where('fee_groups_id', $feeGroupsId)
            ->where('session_id', $sessionId)
            ->first();

        if (! $sessionGroup) {
            return false;
        }

        $query = FeeGroupFeetype::query()
            ->where('fee_session_group_id', $sessionGroup->id)
            ->where('fee_groups_id', $feeGroupsId)
            ->where('feetype_id', $feetypeId)
            ->where('session_id', $sessionId);

        if ($ignoreRowId > 0) {
            $query->where('id', '!=', $ignoreRowId);
        }

        return $query->exists();
    }

    /**
     * @param  array{fee_groups_id:int,feetype_id:int,amount:float|string,due_date:?string,fine_type:string,fine_percentage?:float|string|null,fine_amount?:float|string|null,fine_per_day?:int}  $data
     */
    public function addRow(array $data): FeeGroupFeetype
    {
        return DB::transaction(function () use ($data) {
            $sessionId = $this->currentSession->id();
            $sessionGroup = $this->ensureSessionGroup((int) $data['fee_groups_id']);
            $fineType = $data['fine_type'] ?: 'none';

            return FeeGroupFeetype::query()->create([
                'fee_session_group_id' => $sessionGroup->id,
                'fee_groups_id' => (int) $data['fee_groups_id'],
                'feetype_id' => (int) $data['feetype_id'],
                'session_id' => $sessionId,
                'amount' => (float) $data['amount'],
                'due_date' => $fineType === 'none' ? ($data['due_date'] ?: null) : ($data['due_date'] ?: null),
                'fine_type' => $fineType,
                'fine_percentage' => (float) ($data['fine_percentage'] ?? 0),
                'fine_amount' => (float) ($data['fine_amount'] ?? 0),
                'fine_per_day' => (int) ($data['fine_per_day'] ?? 0),
                'is_active' => 'no',
            ]);
        });
    }

    /**
     * @param  array{feetype_id:int,amount:float|string,due_date:?string,fine_type:string,fine_percentage?:float|string|null,fine_amount?:float|string|null,fine_per_day?:int}  $data
     */
    public function updateRow(FeeGroupFeetype $row, array $data): FeeGroupFeetype
    {
        $fineType = $data['fine_type'] ?: 'none';
        $row->feetype_id = (int) $data['feetype_id'];
        $row->amount = (float) $data['amount'];
        $row->due_date = $data['due_date'] ?: null;
        $row->fine_type = $fineType;
        $row->fine_percentage = (float) ($data['fine_percentage'] ?? 0);
        $row->fine_amount = (float) ($data['fine_amount'] ?? 0);
        $row->fine_per_day = (int) ($data['fine_per_day'] ?? 0);
        $row->save();

        return $row;
    }

    public function deleteRow(int $id): void
    {
        FeeGroupFeetype::query()->where('id', $id)->delete();
    }

    public function deleteSessionGroup(int $feeSessionGroupId): void
    {
        DB::transaction(function () use ($feeSessionGroupId) {
            FeeGroupFeetype::query()->where('fee_session_group_id', $feeSessionGroupId)->delete();
            FeeSessionGroup::query()->where('id', $feeSessionGroupId)->delete();
        });
    }
}
