<?php

namespace App\Modules\Payments\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Payments\Services\GatewayInsService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * CI gateway_ins/{gateway} callbacks — persist posted payload; settlement deferred.
 */
class GatewayInsCallbackController extends Controller
{
    public function __construct(
        protected GatewayInsService $gatewayIns,
    ) {
    }

    public function index(Request $request, string $gateway): Response
    {
        $gatewayName = strtolower($gateway);
        $uniqueId = $this->resolveUniqueId($request);
        if ($uniqueId === null || $uniqueId === '') {
            return response('', 200);
        }

        $row = $this->gatewayIns->getGatewayIns($uniqueId, $gatewayName);
        if ($row === null) {
            return response('', 200);
        }

        $status = $this->resolveStatus($request);

        $this->gatewayIns->addGatewayInsResponse([
            'gateway_ins_id' => (int) $row['id'],
            'posted_data' => json_encode($request->all()),
            'response' => (string) $status,
        ]);

        $this->gatewayIns->updateGatewayIns([
            'id' => (int) $row['id'],
            'payment_status' => (string) $status,
        ]);

        return response('', 200);
    }

    protected function resolveUniqueId(Request $request): ?string
    {
        foreach ([
            'order_id',
            'billExternalReferenceNo',
            'pf_payment_id',
            'txn_id',
            'transaction_id',
            'merchant_order_id',
            'reference_no',
        ] as $key) {
            $value = $request->input($key);
            if (is_string($value) && $value !== '') {
                return $value;
            }
            if (is_numeric($value)) {
                return (string) $value;
            }
        }

        return null;
    }

    protected function resolveStatus(Request $request): string|int
    {
        foreach (['status', 'payment_status', 'STATUS', 'paymentStatus'] as $key) {
            if ($request->has($key)) {
                return $request->input($key);
            }
        }

        return 'received';
    }
}
