<?php

namespace App\Modules\Payments\Services;

use App\Modules\Fees\Services\PortalOnlinePayService;
use Illuminate\Support\Facades\DB;

/**
 * CI user/gateway/{method}::pay — create gateway_ins + student_fees_processing before live charge.
 * Live gateway APIs and fee_deposit_bulk settlement remain deferred.
 */
class StudentFeeGatewayPersistService
{
    public function __construct(
        protected GatewayInsService $gatewayIns,
    ) {
    }

    /**
     * Idempotent persist for the current portal checkout session.
     *
     * @param  array<string, mixed>  $params
     * @return array<string, mixed>
     */
    public function persistFromSession(array $params, string $gateway): array
    {
        $gatewayName = strtolower(trim($gateway));
        if ($gatewayName === '') {
            return $params;
        }

        $existingId = (int) ($params['gateway_ins_id'] ?? 0);
        if ($existingId > 0) {
            $row = DB::table('gateway_ins')->where('id', $existingId)->first();
            if ($row !== null && (string) $row->module_type === 'fees') {
                return $params;
            }
        }

        $lines = $params['lines'] ?? [];
        if (! is_array($lines) || $lines === []) {
            return $params;
        }

        $uniqueId = (string) ($params['transaction_id'] ?? (time().random_int(99, 999)));
        $processingChargeType = (string) ($params['processing_charge_type'] ?? '');
        $processingCharge = (float) ($params['gateway_processing_charge'] ?? 0);
        $paymentMode = $this->paymentModeLabel($gatewayName);

        $parameterDetails = json_encode([
            'unique_id' => $uniqueId,
            'gateway_name' => $gatewayName,
            'module_type' => 'fees',
            'student_session_id' => (int) ($params['student_session_id'] ?? 0),
            'student_id' => (int) ($params['student_id'] ?? 0),
            'total' => (float) ($params['total'] ?? 0),
            'live_charge_deferred' => true,
        ]);

        return DB::transaction(function () use ($params, $gatewayName, $uniqueId, $lines, $parameterDetails, $processingChargeType, $processingCharge, $paymentMode) {
            $gatewayInsId = $this->gatewayIns->addGatewayIns([
                'online_admission_id' => null,
                'unique_id' => $uniqueId,
                'parameter_details' => $parameterDetails,
                'gateway_name' => $gatewayName,
                'module_type' => 'fees',
                'payment_status' => 'processing',
            ]);

            $bulk = [];
            foreach ($lines as $line) {
                if (! is_array($line)) {
                    continue;
                }

                $bulk[] = [
                    'gateway_ins_id' => $gatewayInsId,
                    'fee_category' => (string) ($line['fee_category'] ?? 'fees'),
                    'student_transport_fee_id' => (int) ($line['student_transport_fee_id'] ?? 0) ?: null,
                    'student_fees_master_id' => (int) ($line['student_fees_master_id'] ?? 0) ?: null,
                    'fee_groups_feetype_id' => (int) ($line['fee_groups_feetype_id'] ?? 0) ?: null,
                    'amount_detail' => [
                        'amount' => (float) ($line['amount_balance'] ?? 0),
                        'date' => date('Y-m-d'),
                        'amount_discount' => (float) ($line['applied_fee_discount'] ?? 0),
                        'processing_charge_type' => $processingChargeType,
                        'gateway_processing_charge' => $processingCharge,
                        'amount_fine' => (float) ($line['fine_balance'] ?? 0),
                        'description' => $this->depositDescription($gatewayName, $uniqueId),
                        'received_by' => '',
                        'payment_mode' => $paymentMode,
                    ],
                ];
            }

            if ($bulk !== []) {
                $this->gatewayIns->feeProcessing($bulk);
            }

            $params['gateway_ins_id'] = $gatewayInsId;
            $params['transaction_id'] = $uniqueId;
            session()->put(PortalOnlinePayService::SESSION_PARAMS_KEY, $params);

            return $params;
        });
    }

    protected function paymentModeLabel(string $gatewayName): string
    {
        return match ($gatewayName) {
            'toyyibpay' => 'toyyibPay',
            'payu' => 'PayU',
            'paytm' => 'Paytm',
            default => ucfirst($gatewayName),
        };
    }

    protected function depositDescription(string $gatewayName, string $uniqueId): string
    {
        $normalized = strtolower(str_replace([' ', '-'], '', $gatewayName));
        $key = 'online_fees_deposit_through_'.$normalized.'_txn_id';
        $label = __($key);

        if ($label === $key) {
            $label = __('system.online_fees_deposit').' ';
        }

        return $label.$uniqueId;
    }
}
