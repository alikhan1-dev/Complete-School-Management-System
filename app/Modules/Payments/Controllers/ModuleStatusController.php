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
            'message' => 'Admin payment method credentials + online admission checkout persist done. Deferred: live gateway drivers, webhooks, Studentfee collect, SaaS quota.',
            'slices' => [
                'payment_settings' => 'done',
                'online_admission_checkout' => 'done',
                'gateways' => 'deferred',
                'webhooks' => 'deferred',
            ],
        ]);
    }
}
