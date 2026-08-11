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
            'message' => 'Phase 3b Slice 1: student day attendance (mark/save). Next: by-date report, subject/period, staff attendance.',
            'slices' => [
                'student_day' => 'done',
                'attendance_by_date' => 'pending',
                'subject_period' => 'pending',
                'staff_attendance' => 'pending',
            ],
        ]);
    }
}
