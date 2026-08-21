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
            'message' => 'Student information + Attendencereports full + Financereports slice 1–2 (hub, balance/statement/daily, fees collection report, online fees report) persist done. Deferred: remaining Financereports (duefeesremark, income/expense/payroll/groups, onlineadmission, incomeexpensebalance), Balancefees/due_fees_report, transport fee lines, class-teacher scope, view-students modal, CI pixel-parity JS, student_profile custom-field columns.',
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
                'attendance_reports_hub_daywise_daily_type' => 'done',
                'attendance_reports_monthly_calendars' => 'done',
                'attendance_reports_period_biometric' => 'done',
                'finance_reports_hub_balance_statement_daily' => 'done',
                'finance_reports_collection_online' => 'done',
                'finance_reports_income_expense_payroll_groups' => 'pending',
                'balance_fees' => 'pending',
            ],
        ]);
    }
}
