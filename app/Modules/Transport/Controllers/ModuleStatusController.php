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
            'status' => 'operational_core_done',
            'message' => 'Transport operational core complete: vehicles, routes, vehroute, pickup points, route assign, reorder, student transport report, fees-master, student transport fees assign, pointmap. Deferred polish only if discovered later.',
            'slices' => [
                'vehicles_crud' => 'done',
                'routes' => 'done',
                'vehicle_routes' => 'done',
                'pickup_points' => 'done',
                'route_pickup_assign' => 'done',
                'pickup_reorder' => 'done',
                'pickup_pointmap' => 'done',
                'student_transport_report' => 'done',
                'transport_fees_master' => 'done',
                'student_transport_fees' => 'done',
            ],
        ]);
    }
}
