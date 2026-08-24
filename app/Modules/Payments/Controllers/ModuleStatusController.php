<?php

namespace App\Modules\Payments\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

/**
 * Payments migration status.
 */
class ModuleStatusController extends Controller
{
    public function status(): JsonResponse
    {
        return response()->json([
            'module' => 'Payments',
            'status' => 'in_progress',
            'message' => 'Admin payment settings + online admission checkout + student fee gateway_ins/processing persist + callback/webhook stubs done. Deferred: live gateway drivers, fee settlement (fee_deposit_bulk), SaaS quota.',
            'slices' => [
                'payment_settings' => 'done',
                'online_admission_checkout' => 'done',
                'student_fee_gateway_persist' => 'done',
                'gateway_callbacks' => 'stub',
                'webhooks' => 'stub',
                'gateways' => 'deferred',
            ],
        ]);
    }
}
