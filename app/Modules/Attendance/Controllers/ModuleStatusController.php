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
            'message' => 'Phase 3b: day + by-date + subject/period + staff mark-save done. Deferred: period reportbydate, class-teacher filter, SMS, biometric, staff profile month view.',
            'slices' => [
                'student_day' => 'done',
                'attendance_by_date' => 'done',
                'subject_period' => 'done',
                'subject_period_reportbydate' => 'deferred',
                'staff_attendance' => 'done',
                'staff_profile_month' => 'deferred',
            ],
        ]);
    }
}
