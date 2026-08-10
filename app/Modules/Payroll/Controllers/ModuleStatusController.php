<?php

namespace App\Modules\Payroll\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

/**
 * Placeholder for Phase 2–8 migration of the Payroll module.
 * Feature code will be ported from smart_7.2 with parity testing.
 */
class ModuleStatusController extends Controller
{
    public function status(): JsonResponse
    {
        return response()->json([
            'module' => 'Payroll',
            'status' => 'pending',
            'message' => 'Module skeleton ready. Business features migrate in later phases.',
        ]);
    }
}