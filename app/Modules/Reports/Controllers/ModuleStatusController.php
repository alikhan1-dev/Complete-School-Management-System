<?php

namespace App\Modules\Reports\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

/**
 * Phase 8 Reports migration status.
 */
class ModuleStatusController extends Controller
{
    public function status(): JsonResponse
    {
        return response()->json([
            'module' => 'Reports',
            'status' => 'in_progress',
            'message' => 'Student information hub through online admission report persist done (class subject, date-range admission, sibling, profile, online admission included). Deferred: Attendencereports/Financereports/Balancefees screens, class-teacher scope, view-students modal, CI pixel-parity JS, student_profile custom-field columns.',
            'slices' => [
                'student_information_hub' => 'done',
                'student_report' => 'done',
                'class_section_report' => 'done',
                'gender_ratio' => 'done',
                'teacher_ratio' => 'done',
                'guardian_report' => 'done',
                'student_history' => 'done',
                'login_credentials' => 'done',
                'class_subject_report' => 'done',
                'admission_report' => 'done',
                'sibling_report' => 'done',
                'student_profile' => 'done',
                'online_admission_report' => 'done',
                'attendance_reports' => 'pending',
                'finance_reports' => 'pending',
                'balance_fees' => 'pending',
            ],
        ]);
    }
}
