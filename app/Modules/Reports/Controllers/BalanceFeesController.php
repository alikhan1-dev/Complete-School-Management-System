<?php

namespace App\Modules\Reports\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Reports\Services\FinanceReportService;
use App\Modules\Roles\Services\PermissionService;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * CI Balancefees::index — due_fees_report (transport + class-teacher deferred).
 */
class BalanceFeesController extends Controller
{
    public function __construct(
        protected PermissionService $permissions,
        protected FinanceReportService $reports,
    ) {
    }

    public function index(Request $request): View
    {
        abort_unless($this->permissions->hasPrivilege('due_fees_report', 'can_view'), 403);

        $filters = [
            'class_id' => $request->input('class_id', ''),
            'section_id' => $request->input('section_id', ''),
            'search_type' => $request->input('search_type', 'all'),
        ];
        $studentDueFee = [];
        $searched = false;

        if ($request->isMethod('post')) {
            $request->validate([
                'search_type' => ['required'],
            ], [
                'search_type.required' => 'The Search Type field is required.',
            ]);
            $searched = true;
            $studentDueFee = $this->reports->dueFeesReport(
                $filters['class_id'] !== '' ? (int) $filters['class_id'] : null,
                $filters['section_id'] !== '' ? (int) $filters['section_id'] : null,
                (string) $filters['search_type']
            );
        }

        return view('shared::layouts.admin', array_merge([
            'title' => __('system.due_fees_report'),
            'contentView' => 'reports::admin.finance.due_fees_report',
            'filters' => $filters,
            'student_due_fee' => $studentDueFee,
            'searched' => $searched,
            'classlist' => $this->reports->classes(),
            'reports' => $this->reports,
            'settingOnFatherName' => $this->reports->settingOn('father_name'),
        ], $this->navFlags()));
    }

    /**
     * @return array<string, bool>
     */
    protected function navFlags(): array
    {
        return [
            'canBalanceFeesStatement' => $this->permissions->hasPrivilege('balance_fees_statement', 'can_view'),
            'canDailyCollection' => $this->permissions->hasPrivilege('daily_collection_report', 'can_view'),
            'canFeesStatement' => $this->permissions->hasPrivilege('fees_statement', 'can_view'),
            'canBalanceFeesReport' => $this->permissions->hasPrivilege('balance_fees_report', 'can_view'),
            'canFeesCollectionReport' => $this->permissions->hasPrivilege('fees_collection_report', 'can_view'),
            'canOnlineFeesCollection' => $this->permissions->hasPrivilege('online_fees_collection_report', 'can_view'),
            'canBalanceFeesRemark' => $this->permissions->hasPrivilege('balance_fees_report_with_remark', 'can_view'),
            'canIncomeReport' => $this->permissions->hasPrivilege('income_report', 'can_view'),
            'canExpenseReport' => $this->permissions->hasPrivilege('expense_report', 'can_view'),
            'canPayrollReport' => $this->permissions->hasPrivilege('payroll_report', 'can_view'),
            'canIncomeGroupReport' => $this->permissions->hasPrivilege('income_group_report', 'can_view'),
            'canExpenseGroupReport' => $this->permissions->hasPrivilege('expense_group_report', 'can_view'),
            'canOnlineAdmissionFees' => $this->permissions->hasPrivilege('online_admission_fees_collection_report', 'can_view'),
            'canDueFeesReport' => $this->permissions->hasPrivilege('due_fees_report', 'can_view'),
            'canIncomeExpenseBalance' => $this->permissions->hasPrivilege('income_expense_balance_report', 'can_view'),
        ];
    }
}
