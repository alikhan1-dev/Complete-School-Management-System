<?php

namespace App\Modules\FrontOffice\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

/**
 * Placeholder for Phase 2–8 migration of the FrontOffice module.
 * Feature code will be ported from smart_7.2 with parity testing.
 */
class ModuleStatusController extends Controller
{
    public function status(): JsonResponse
    {
        return response()->json([
            'module' => 'FrontOffice',
            'status' => 'in_progress',
            'message' => 'FrontOffice persist slices done (enquiry, visitors, complaints, dispatch/receive, phone calls, setup masters). Deferred: SaaS quota.',
            'slices' => [
                'admission_enquiry' => 'done',
                'visitors' => 'done',
                'complaints' => 'done',
                'dispatch_receive' => 'done',
                'phone_calls' => 'done',
                'setup_masters' => 'done',
            ],
        ]);
    }
}