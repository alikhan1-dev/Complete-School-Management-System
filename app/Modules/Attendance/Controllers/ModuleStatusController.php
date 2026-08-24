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
            'message' => 'Phase 3b: day + by-date + subject/period + staff mark-save + period reportbydate + staff profile month view done. Deferred: class-teacher filter, SMS, biometric.',
            'slices' => [
                'student_day' => 'done',
                'attendance_by_date' => 'done',
                'subject_period' => 'done',
                'subject_period_reportbydate' => 'done',
                'staff_attendance' => 'done',
                'staff_profile_month' => 'done',
            ],
        ]);
    }
}
