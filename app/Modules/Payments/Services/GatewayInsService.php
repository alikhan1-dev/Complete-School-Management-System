<?php

namespace App\Modules\Payments\Services;

use App\Modules\Fees\Models\StudentFeesProcessing;
use App\Modules\Payments\Models\GatewayIns;
use App\Modules\Payments\Models\GatewayInsResponse;
use Illuminate\Support\Facades\DB;

/**
 * CI Gateway_ins_model — gateway checkout + processing fee rows (no live settlement).
 */
class GatewayInsService
{
    /**
     * @param  array<string, mixed>  $gatewayIns
     */
    public function addGatewayIns(array $gatewayIns): int
    {
        return (int) GatewayIns::query()->insertGetId([
            'online_admission_id' => $gatewayIns['online_admission_id'] ?? null,
            'gateway_name' => (string) $gatewayIns['gateway_name'],
            'module_type' => (string) $gatewayIns['module_type'],
            'unique_id' => (string) $gatewayIns['unique_id'],
            'parameter_details' => (string) $gatewayIns['parameter_details'],
            'payment_status' => (string) $gatewayIns['payment_status'],
        ]);
    }

    /**
     * @param  array<string, mixed>  $gatewayIns
     */
    public function updateGatewayIns(array $gatewayIns): int
    {
        $id = (int) ($gatewayIns['id'] ?? 0);
        if ($id <= 0) {
            return 0;
        }

        unset($gatewayIns['id']);
        GatewayIns::query()->where('id', $id)->update($gatewayIns);

        return $id;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getGatewayIns(string $uniqueId, string $gatewayName): ?array
    {
        $row = GatewayIns::query()
            ->where('gateway_name', strtolower($gatewayName))
            ->where('unique_id', $uniqueId)
            ->first();

        return $row?->toArray();
    }

    /**
     * @param  array<string, mixed>  $response
     */
    public function addGatewayInsResponse(array $response): int
    {
        return (int) GatewayInsResponse::query()->insertGetId([
            'gateway_ins_id' => $response['gateway_ins_id'] ?? null,
            'posted_data' => (string) ($response['posted_data'] ?? ''),
            'response' => (string) ($response['response'] ?? ''),
        ]);
    }

    public function deleteProcessingByGatewayInsId(int $gatewayInsId): void
    {
        if ($gatewayInsId <= 0) {
            return;
        }

        DB::table('student_fees_processing')->where('gateway_ins_id', $gatewayInsId)->delete();
    }

    /**
     * CI Gateway_ins_model::fee_processing.
     *
     * @param  list<array<string, mixed>>  $bulkData
     */
    public function feeProcessing(array $bulkData): ?int
    {
        $lastId = null;

        foreach ($bulkData as $feeData) {
            $category = (string) ($feeData['fee_category'] ?? 'fees');
            if ($category === 'fees') {
                $feeData['student_transport_fee_id'] = null;
            } elseif ($category === 'transport' && (int) ($feeData['student_transport_fee_id'] ?? 0) > 0) {
                $feeData['student_fees_master_id'] = null;
                $feeData['fee_groups_feetype_id'] = null;
            }

            $amountDetail = $feeData['amount_detail'] ?? [];
            if (is_array($amountDetail)) {
                $feeData['amount_detail'] = json_encode($amountDetail);
            }

            $row = StudentFeesProcessing::query()->create([
                'gateway_ins_id' => (int) $feeData['gateway_ins_id'],
                'fee_category' => $category,
                'student_fees_master_id' => $feeData['student_fees_master_id'] ?? null,
                'fee_groups_feetype_id' => $feeData['fee_groups_feetype_id'] ?? null,
                'student_transport_fee_id' => $feeData['student_transport_fee_id'] ?? null,
                'amount_detail' => (string) $feeData['amount_detail'],
                'is_active' => 'no',
            ]);

            $lastId = (int) $row->id;
        }

        return $lastId;
    }
}
