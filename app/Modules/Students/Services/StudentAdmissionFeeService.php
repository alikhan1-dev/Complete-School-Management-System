<?php

namespace App\Modules\Students\Services;

use App\Modules\Academics\Services\CurrentSessionResolver;
use App\Modules\Fees\Models\FeesDiscount;
use App\Modules\Fees\Models\FeeSessionGroup;
use App\Modules\Fees\Models\StudentFeesDiscount;
use App\Modules\Fees\Models\StudentFeesMaster;
use App\Modules\Fees\Services\FeeMasterService;
use App\Modules\Transport\Models\StudentTransportFee;
use App\Modules\Transport\Models\TransportFeemaster;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * CI Student_model::addNewMethod / UpdateNewMethod fee + discount + transport assign slices.
 */
class StudentAdmissionFeeService
{
    public function __construct(
        protected FeeMasterService $feeMasters,
        protected CurrentSessionResolver $currentSession,
    ) {
    }

    /**
     * @return Collection<int, FeeSessionGroup>
     */
    public function feeSessionGroupsForForm(): Collection
    {
        return $this->feeMasters->listForCurrentSession(true);
    }

    /**
     * @return Collection<int, FeesDiscount>
     */
    public function feeDiscountsForForm(): Collection
    {
        return FeesDiscount::query()->orderBy('id')->get();
    }

    /**
     * Transport fee months that exist for the current session (id > 0).
     *
     * @return list<object{id:int,month:string}>
     */
    public function transportFeeMonthsForForm(): array
    {
        $sessionId = (int) $this->currentSession->id();

        return TransportFeemaster::query()
            ->where('session_id', $sessionId)
            ->orderBy('id')
            ->get(['id', 'month', 'session_id'])
            ->all();
    }

    /**
     * @return list<object>
     */
    public function routePickupPointsForForm(): array
    {
        return DB::table('route_pickup_point')
            ->leftJoin('transport_route', 'transport_route.id', '=', 'route_pickup_point.transport_route_id')
            ->leftJoin('pickup_point', 'pickup_point.id', '=', 'route_pickup_point.pickup_point_id')
            ->orderBy('route_pickup_point.id')
            ->select([
                'route_pickup_point.id',
                'route_pickup_point.transport_route_id',
                'transport_route.route_title',
                'pickup_point.name as pickup_point_name',
            ])
            ->get()
            ->all();
    }

    /**
     * @return list<int>
     */
    public function assignedFeeSessionGroupIds(int $studentSessionId): array
    {
        return StudentFeesMaster::query()
            ->where('student_session_id', $studentSessionId)
            ->pluck('fee_session_group_id')
            ->map(fn ($id) => (int) $id)
            ->all();
    }

    /**
     * @return list<int>
     */
    public function assignedDiscountIds(int $studentSessionId): array
    {
        return StudentFeesDiscount::query()
            ->where('student_session_id', $studentSessionId)
            ->pluck('fees_discount_id')
            ->map(fn ($id) => (int) $id)
            ->all();
    }

    /**
     * @return list<int>
     */
    public function assignedTransportFeemasterIds(int $studentSessionId): array
    {
        return StudentTransportFee::query()
            ->where('student_session_id', $studentSessionId)
            ->pluck('transport_feemaster_id')
            ->map(fn ($id) => (int) $id)
            ->all();
    }

    /**
     * CI addNewMethod fee / discount / transport inserts.
     *
     * @param  list<int|string>  $feeSessionGroupIds
     * @param  list<int|string>  $feesDiscountIds
     * @param  list<int|string>  $transportFeemasterIds
     */
    public function assignOnAdmit(
        int $studentSessionId,
        array $feeSessionGroupIds,
        array $feesDiscountIds,
        array $transportFeemasterIds = [],
        ?int $routePickupPointId = null,
    ): void {
        $feeSessionGroupIds = $this->normalizeIds($feeSessionGroupIds);
        $feesDiscountIds = $this->normalizeIds($feesDiscountIds);
        $transportFeemasterIds = $this->normalizeIds($transportFeemasterIds);

        foreach ($feeSessionGroupIds as $feeSessionGroupId) {
            StudentFeesMaster::query()->create([
                'is_system' => 0,
                'student_session_id' => $studentSessionId,
                'fee_session_group_id' => $feeSessionGroupId,
                'amount' => 0,
                'is_active' => 'no',
            ]);
        }

        foreach ($feesDiscountIds as $discountId) {
            StudentFeesDiscount::query()->create([
                'student_session_id' => $studentSessionId,
                'fees_discount_id' => $discountId,
                'status' => 'assigned',
                'payment_id' => null,
                'description' => null,
                'is_active' => 'no',
            ]);
        }

        if ($transportFeemasterIds !== [] && $routePickupPointId !== null && $routePickupPointId > 0) {
            foreach ($transportFeemasterIds as $transportFeemasterId) {
                StudentTransportFee::query()->create([
                    'student_session_id' => $studentSessionId,
                    'route_pickup_point_id' => $routePickupPointId,
                    'transport_feemaster_id' => $transportFeemasterId,
                ]);
            }
        }
    }

    /**
     * CI UpdateNewMethod fee / discount / transport sync.
     *
     * @param  list<int|string>  $feeSessionGroupIds
     * @param  list<int|string>  $feesDiscountIds
     * @param  list<int|string>  $transportFeemasterIds
     */
    public function syncOnEdit(
        int $studentSessionId,
        array $feeSessionGroupIds,
        array $feesDiscountIds,
        array $transportFeemasterIds = [],
        ?int $routePickupPointId = null,
    ): void {
        $desiredFees = $this->normalizeIds($feeSessionGroupIds);
        $desiredDiscounts = $this->normalizeIds($feesDiscountIds);
        $desiredTransport = $this->normalizeIds($transportFeemasterIds);

        $existingFees = $this->assignedFeeSessionGroupIds($studentSessionId);
        $toAddFees = array_values(array_diff($desiredFees, $existingFees));
        $toDeleteFees = array_values(array_diff($existingFees, $desiredFees));

        foreach ($toAddFees as $feeSessionGroupId) {
            StudentFeesMaster::query()->create([
                'is_system' => 0,
                'student_session_id' => $studentSessionId,
                'fee_session_group_id' => $feeSessionGroupId,
                'amount' => 0,
                'is_active' => 'no',
            ]);
        }
        if ($toDeleteFees !== []) {
            StudentFeesMaster::query()
                ->where('student_session_id', $studentSessionId)
                ->whereIn('fee_session_group_id', $toDeleteFees)
                ->delete();
        }

        $existingDiscounts = $this->assignedDiscountIds($studentSessionId);
        $toAddDiscounts = array_values(array_diff($desiredDiscounts, $existingDiscounts));
        $toDeleteDiscounts = array_values(array_diff($existingDiscounts, $desiredDiscounts));

        foreach ($toAddDiscounts as $discountId) {
            StudentFeesDiscount::query()->create([
                'student_session_id' => $studentSessionId,
                'fees_discount_id' => $discountId,
                'status' => 'assigned',
                'payment_id' => null,
                'description' => null,
                'is_active' => 'no',
            ]);
        }
        if ($toDeleteDiscounts !== []) {
            StudentFeesDiscount::query()
                ->where('student_session_id', $studentSessionId)
                ->whereIn('fees_discount_id', $toDeleteDiscounts)
                ->delete();
        }

        if ($desiredTransport === [] || $routePickupPointId === null || $routePickupPointId <= 0) {
            StudentTransportFee::query()->where('student_session_id', $studentSessionId)->delete();

            return;
        }

        $keptIds = [];
        foreach ($desiredTransport as $transportFeemasterId) {
            $existing = StudentTransportFee::query()
                ->where('student_session_id', $studentSessionId)
                ->where('route_pickup_point_id', $routePickupPointId)
                ->where('transport_feemaster_id', $transportFeemasterId)
                ->first();

            if ($existing) {
                $keptIds[] = (int) $existing->id;
            } else {
                $created = StudentTransportFee::query()->create([
                    'student_session_id' => $studentSessionId,
                    'route_pickup_point_id' => $routePickupPointId,
                    'transport_feemaster_id' => $transportFeemasterId,
                ]);
                $keptIds[] = (int) $created->id;
            }
        }

        StudentTransportFee::query()
            ->where('student_session_id', $studentSessionId)
            ->whereNotIn('id', $keptIds)
            ->delete();
    }

    /**
     * @param  list<int|string>|null  $ids
     * @return list<int>
     */
    protected function normalizeIds(?array $ids): array
    {
        if ($ids === null || $ids === []) {
            return [];
        }

        return array_values(array_unique(array_filter(array_map('intval', $ids), fn (int $id) => $id > 0)));
    }
}
