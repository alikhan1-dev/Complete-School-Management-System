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
            'message' => 'Student information hub + student report + class/section counts + gender/teacher ratio + guardian + student history + student/parent login credentials persist done. Deferred: remaining Report/Attendencereports/Financereports/Balancefees screens, class-teacher scope, view-students modal, CI pixel-parity JS.',
            'slices' => [
                'student_information_hub' => 'done',
                'student_report' => 'done',
                'class_section_report' => 'done',
                'gender_ratio' => 'done',
                'teacher_ratio' => 'done',
                'guardian_report' => 'done',
                'student_history' => 'done',
                'login_credentials' => 'done',
                'class_subject_admission_sibling_profile_online_admission' => 'pending',
                'attendance_reports' => 'pending',
                'finance_reports' => 'pending',
                'balance_fees' => 'pending',
            ],
        ]);
    }
}
