<?php

namespace App\Modules\Hostel\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

/**
 * Phase 6 Hostel migration status.
 */
class ModuleStatusController extends Controller
{
    public function status(): JsonResponse
    {
        return response()->json([
            'module' => 'Hostel',
            'status' => 'done',
            'message' => 'Hostel admin core + student hostel report incl. class-teacher scope done.',
            'slices' => [
                'room_types' => 'done',
                'hostels' => 'done',
                'hostel_rooms' => 'done',
                'student_hostel_report' => 'done',
                'student_hostel_report_class_teacher' => 'done',
            ],
        ]);
    }
}
