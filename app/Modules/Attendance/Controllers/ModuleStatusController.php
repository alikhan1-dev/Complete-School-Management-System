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
            'message' => 'Phase 3b: day + by-date + subject/period + staff mark-save + period reportbydate + staff profile month + full class-teacher scope (day/by-date + subject/period) done. Deferred: SMS, biometric.',
            'slices' => [
                'student_day' => 'done',
                'attendance_by_date' => 'done',
                'day_by_date_class_teacher_scope' => 'done',
                'subject_period' => 'done',
                'subject_period_reportbydate' => 'done',
                'subject_period_class_teacher_scope' => 'done',
                'staff_attendance' => 'done',
                'staff_profile_month' => 'done',
                'sms_notifications' => 'deferred',
                'biometric' => 'deferred',
            ],
        ]);
    }
}
