<?php

namespace App\Modules\Fees\Services;

use App\Modules\Fees\Models\StudentFeesProcessing;
use Illuminate\Support\Facades\DB;

/**
 * CI Studentfeemaster_model::getStudentProcessingFees / getProcessingTransportFees
 * + user/User::getProcessingfees modal data (read-only pending gateway payments).
 */
class ProcessingFeesService
{
    public function __construct(
        protected FeeCollectService $collect,
        protected CumulativeFineCalculator $cumulativeFine,
    ) {
    }

    /**
     * CI getfees button flag: true when any assigned fee-master has processing fee lines.
     * (Transport-only processing does not enable the button — CI parity.)
     */
    public function hasProcessingFees(int $studentSessionId): bool
    {
        if ($studentSessionId <= 0) {
            return false;
        }

        return DB::table('student_fees_processing')
            ->join('student_fees_master', 'student_fees_master.id', '=', 'student_fees_processing.student_fees_master_id')
            ->where('student_fees_master.student_session_id', $studentSessionId)
            ->exists();
    }

    /**
     * CI getStudentProcessingFees — masters with nested processing fee lines.
     *
     * @return list<object{id:int,name:string,is_system:int,amount:float,fee_session_group_id:int,fees:list<object>}>
     */
    public function getStudentProcessingFees(int $studentSessionId): array
    {
        $masters = DB::table('student_fees_master')
            ->join('fee_session_groups', 'student_fees_master.fee_session_group_id', '=', 'fee_session_groups.id')
            ->join('fee_groups', 'fee_groups.id', '=', 'fee_session_groups.fee_groups_id')
            ->where('student_fees_master.student_session_id', $studentSessionId)
            ->orderBy('student_fees_master.id')
            ->select([
                'student_fees_master.id',
                'student_fees_master.is_system',
                'student_fees_master.amount',
                'student_fees_master.fee_session_group_id',
                'fee_groups.name',
            ])
            ->get();

        $result = [];
        foreach ($masters as $master) {
            $fees = $this->getProcessingFeeByFeeSessionGroup(
                (int) $master->fee_session_group_id,
                (int) $master->id
            );

            if ($fees !== [] && (int) $master->is_system !== 0) {
                $fees[0]->amount = (float) $master->amount;
            }

            $result[] = (object) [
                'id' => (int) $master->id,
                'name' => (string) $master->name,
                'is_system' => (int) $master->is_system,
                'amount' => (float) $master->amount,
                'fee_session_group_id' => (int) $master->fee_session_group_id,
                'fees' => $fees,
            ];
        }

        return $result;
    }

    /**
     * CI getProcessingFeeByFeeSessionGroup.
     *
     * @return list<object>
     */
    public function getProcessingFeeByFeeSessionGroup(int $feeSessionGroupId, int $studentFeesMasterId): array
    {
        $rows = DB::table('student_fees_master')
            ->join('fee_session_groups', 'fee_session_groups.id', '=', 'student_fees_master.fee_session_group_id')
            ->join('fee_groups_feetype', 'fee_groups_feetype.fee_session_group_id', '=', 'fee_session_groups.id')
            ->join('fee_groups', 'fee_groups.id', '=', 'fee_groups_feetype.fee_groups_id')
            ->join('feetype', 'feetype.id', '=', 'fee_groups_feetype.feetype_id')
            ->join('student_fees_processing', function ($join) {
                $join->on('student_fees_processing.student_fees_master_id', '=', 'student_fees_master.id')
                    ->on('student_fees_processing.fee_groups_feetype_id', '=', 'fee_groups_feetype.id');
            })
            ->join('gateway_ins', 'gateway_ins.id', '=', 'student_fees_processing.gateway_ins_id')
            ->where('student_fees_master.fee_session_group_id', $feeSessionGroupId)
            ->where('student_fees_master.id', $studentFeesMasterId)
            ->orderBy('fee_groups_feetype.due_date')
            ->select([
                'student_fees_master.id as student_fees_master_id',
                'student_fees_master.is_system',
                'student_fees_master.amount as student_fees_master_amount',
                'fee_groups_feetype.fine_type',
                'fee_groups_feetype.id as fee_groups_feetype_id',
                'fee_groups_feetype.amount',
                'fee_groups_feetype.due_date',
                'fee_groups_feetype.fine_amount',
                'fee_groups_feetype.fine_percentage',
                'fee_groups_feetype.fee_groups_id',
                'fee_groups.name',
                'fee_groups_feetype.feetype_id',
                'feetype.code',
                'feetype.type',
                DB::raw('IFNULL(student_fees_processing.id, 0) as student_fees_deposite_id'),
                DB::raw('IFNULL(student_fees_processing.amount_detail, 0) as amount_detail'),
                'gateway_ins.unique_id',
            ])
            ->get();

        $out = [];
        foreach ($rows as $row) {
            $detail = StudentFeesProcessing::parseAmountDetail($row->amount_detail);
            $amount = ((int) $row->is_system !== 0)
                ? (float) $row->student_fees_master_amount
                : (float) $row->amount;
            $paid = $detail['amount'] ?? 0.0;
            $discount = $detail['amount_discount'] ?? 0.0;
            $fine = $detail['amount_fine'] ?? 0.0;
            $balance = round($amount - ($paid + $discount), 2);
            $overdueFine = $this->displayOverdueFine($row, $amount);

            $out[] = (object) [
                'student_fees_master_id' => (int) $row->student_fees_master_id,
                'fee_groups_feetype_id' => (int) $row->fee_groups_feetype_id,
                'is_system' => (int) $row->is_system,
                'name' => (string) $row->name,
                'type' => (string) $row->type,
                'code' => (string) $row->code,
                'amount' => $amount,
                'due_date' => $row->due_date,
                'fine_type' => (string) ($row->fine_type ?? ''),
                'fine_amount' => (float) ($row->fine_amount ?? 0),
                'fine_percentage' => (float) ($row->fine_percentage ?? 0),
                'student_fees_deposite_id' => (int) $row->student_fees_deposite_id,
                'amount_detail' => $row->amount_detail,
                'unique_id' => (string) $row->unique_id,
                'paid_amount' => $paid,
                'paid_discount' => $discount,
                'paid_fine' => $fine,
                'balance' => $balance,
                'overdue_fine' => $overdueFine,
                'payment' => $detail,
            ];
        }

        return $out;
    }

    /**
     * CI getProcessingTransportFees.
     *
     * @return list<object>
     */
    public function getProcessingTransportFees(int $studentSessionId, ?int $routePickupPointId): array
    {
        if ($studentSessionId <= 0 || $routePickupPointId === null || $routePickupPointId <= 0) {
            return [];
        }

        $rows = DB::table('student_transport_fees')
            ->join('transport_feemaster', 'transport_feemaster.id', '=', 'student_transport_fees.transport_feemaster_id')
            ->join('student_fees_processing', 'student_fees_processing.student_transport_fee_id', '=', 'student_transport_fees.id')
            ->join('route_pickup_point', 'route_pickup_point.id', '=', 'student_transport_fees.route_pickup_point_id')
            ->join('gateway_ins', 'gateway_ins.id', '=', 'student_fees_processing.gateway_ins_id')
            ->where('student_transport_fees.student_session_id', $studentSessionId)
            ->where('student_transport_fees.route_pickup_point_id', $routePickupPointId)
            ->orderBy('student_transport_fees.id')
            ->select([
                'student_transport_fees.id as student_transport_fee_id',
                'transport_feemaster.month',
                'transport_feemaster.due_date',
                'route_pickup_point.fees',
                'transport_feemaster.fine_amount',
                'transport_feemaster.fine_type',
                'transport_feemaster.fine_percentage',
                DB::raw('IFNULL(student_fees_processing.id, 0) as student_fees_processing_id'),
                DB::raw('IFNULL(student_fees_processing.amount_detail, 0) as amount_detail'),
                'gateway_ins.unique_id',
            ])
            ->get();

        $out = [];
        foreach ($rows as $row) {
            $detail = StudentFeesProcessing::parseAmountDetail($row->amount_detail);
            $amount = (float) $row->fees;
            $paid = $detail['amount'] ?? 0.0;
            $discount = $detail['amount_discount'] ?? 0.0;
            $fine = $detail['amount_fine'] ?? 0.0;
            $balance = round($amount - ($paid + $discount), 2);
            $overdueFine = $this->transportOverdueFine($row, $amount);

            $out[] = (object) [
                'student_transport_fee_id' => (int) $row->student_transport_fee_id,
                'month' => (string) $row->month,
                'due_date' => $row->due_date,
                'fees' => $amount,
                'fine_amount' => (float) ($row->fine_amount ?? 0),
                'fine_type' => (string) ($row->fine_type ?? ''),
                'fine_percentage' => (float) ($row->fine_percentage ?? 0),
                'student_fees_processing_id' => (int) $row->student_fees_processing_id,
                'amount_detail' => $row->amount_detail,
                'unique_id' => (string) $row->unique_id,
                'paid_amount' => $paid,
                'paid_discount' => $discount,
                'paid_fine' => $fine,
                'balance' => $balance,
                'overdue_fine' => $overdueFine,
                'payment' => $detail,
            ];
        }

        return $out;
    }

    /**
     * @return array{
     *     student:object,
     *     student_due_fee:list<object>,
     *     transport_fees:list<object>
     * }
     */
    public function modalData(int $studentSessionId): array
    {
        $student = $this->collect->findStudentBySession($studentSessionId);
        if (! $student) {
            throw new \RuntimeException('Student not found for selected class.');
        }

        $routeId = isset($student->route_pickup_point_id) ? (int) $student->route_pickup_point_id : 0;
        $transport = ($routeId > 0)
            ? $this->getProcessingTransportFees($studentSessionId, $routeId)
            : [];

        return [
            'student' => $student,
            'student_due_fee' => $this->getStudentProcessingFees($studentSessionId),
            'transport_fees' => $transport,
        ];
    }

    protected function displayOverdueFine(object $row, float $amount): float
    {
        $dueDate = $row->due_date ?? null;
        if (empty($dueDate) || $dueDate === '0000-00-00') {
            return 0.0;
        }
        if (strtotime((string) $dueDate) >= strtotime(date('Y-m-d'))) {
            return 0.0;
        }

        $fineType = (string) ($row->fine_type ?? '');
        if ($fineType === 'cumulative') {
            $dueDays = (int) (new \DateTimeImmutable((string) $dueDate))
                ->diff(new \DateTimeImmutable(date('Y-m-d')))
                ->format('%a');
            $calculated = $this->cumulativeFine->amountFor((int) $row->fee_groups_feetype_id, $dueDays);

            return $calculated === false ? 0.0 : (float) $calculated;
        }

        if ($fineType === 'fix' || $fineType === 'percentage') {
            // CI processing view uses fine_amount for both fix and percentage rows.
            return (float) ($row->fine_amount ?? 0);
        }

        return 0.0;
    }

    protected function transportOverdueFine(object $row, float $fees): float
    {
        $dueDate = $row->due_date ?? null;
        if (empty($dueDate) || $dueDate === '0000-00-00') {
            return 0.0;
        }
        if (strtotime((string) $dueDate) >= strtotime(date('Y-m-d'))) {
            return 0.0;
        }

        if ((string) ($row->fine_type ?? '') === 'percentage') {
            $pct = (float) ($row->fine_percentage ?? 0);

            return round($fees * $pct / 100, 2);
        }

        return (float) ($row->fine_amount ?? 0);
    }
}
