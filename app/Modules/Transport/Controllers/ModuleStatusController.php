<?php

namespace App\Modules\Transport\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

/**
 * Phase 6 Transport migration status.
 */
class ModuleStatusController extends Controller
{
    public function status(): JsonResponse
    {
        return response()->json([
            'module' => 'Transport',
            'status' => 'in_progress',
            'message' => 'Transport admin + student transport report + fees-master done. Deferred: reorder, student transport fees assign, maps.',
            'slices' => [
                'vehicles_crud' => 'done',
                'routes' => 'done',
                'vehicle_routes' => 'done',
                'pickup_points' => 'done',
                'route_pickup_assign' => 'done',
                'student_transport_report' => 'done',
                'transport_fees_master' => 'done',
                'student_transport_fees' => 'deferred',
                'pickup_reorder' => 'deferred',
                'maps' => 'deferred',
            ],
        ]);
    }
}
