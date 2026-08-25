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
            'message' => 'Student information (incl. alumni report + class-teacher scope + student_profile table custom fields + class-section view-students modal) + Attendencereports full (incl. class-teacher scope) + Financereports (incl. class-teacher scope + transport fee lines) + Balancefees/due_fees_report + Human Resource hub/staff_report + Lesson Plan syllabus/teacher reports + Online Exam hub/exams report persist done. Deferred: client print/excel checkbox parity, CI pixel-parity JS/DataTables/Chart.js.',
            'slices' => [
                'student_information_hub' => 'done',
                'student_report' => 'done',
                'class_section_report' => 'done',
                'class_section_view_students_modal' => 'done',
                'gender_ratio' => 'done',
                'teacher_ratio' => 'done',
                'guardian_report' => 'done',
                'student_history' => 'done',
                'login_credentials' => 'done',
                'class_subject_report' => 'done',
                'admission_report' => 'done',
                'sibling_report' => 'done',
                'student_profile' => 'done',
                'student_profile_table_custom_fields' => 'done',
                'online_admission_report' => 'done',
                'alumni_report' => 'done',
                'student_information_class_teacher' => 'done',
                'attendance_reports_hub_daywise_daily_type' => 'done',
                'attendance_reports_monthly_calendars' => 'done',
                'attendance_reports_period_biometric' => 'done',
                'attendance_reports_class_teacher' => 'done',
                'finance_reports_hub_balance_statement_daily' => 'done',
                'finance_reports_collection_online' => 'done',
                'finance_reports_remark_payroll_onlineadmission' => 'done',
                'finance_reports_income_expense_balance' => 'done',
                'finance_reports_income_expense_list' => 'done',
                'finance_reports_income_expense_groups' => 'done',
                'balance_fees' => 'done',
                'finance_reports_class_teacher' => 'done',
                'finance_reports_transport_fee_lines' => 'done',
                'human_resource_hub_staff_report' => 'done',
                'lesson_plan_syllabus_teacher_reports' => 'done',
                'online_exam_hub_exams_report' => 'done',
            ],
        ]);
    }
}
