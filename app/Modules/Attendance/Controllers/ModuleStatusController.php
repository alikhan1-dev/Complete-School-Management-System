<?php

namespace App\Modules\Attendance\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

/**
 * Phase 3b Attendance migration status.
 */
class ModuleStatusController extends Controller
{
    public function status(): JsonResponse
    {
        return response()->json([
            'module' => 'Attendance',
            'status' => 'in_progress',
            'message' => 'Phase 3b: student day attendance + by-date report done. Next: subject/period, staff attendance. Deferred: class-teacher filter, SMS, biometric.',
            'slices' => [
                'student_day' => 'done',
                'attendance_by_date' => 'done',
                'subject_period' => 'pending',
                'staff_attendance' => 'pending',
            ],
        ]);
    }
}
