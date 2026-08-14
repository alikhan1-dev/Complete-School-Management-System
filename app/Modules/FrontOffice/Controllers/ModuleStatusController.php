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
            'message' => 'Admission enquiry persist (list/search/CRUD/follow-up) done. Deferred: visitors, complaints, dispatch, phone calls, setup masters.',
            'slices' => [
                'admission_enquiry' => 'done',
                'visitors' => 'pending',
                'complaints' => 'pending',
                'dispatch_receive' => 'pending',
                'phone_calls' => 'pending',
            ],
        ]);
    }
}