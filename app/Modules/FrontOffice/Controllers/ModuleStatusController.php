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
            'status' => 'pending',
            'message' => 'Module skeleton ready. Business features migrate in later phases.',
        ]);
    }
}