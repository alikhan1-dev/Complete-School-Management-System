<?php

namespace App\Modules\Reports\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Reports\Services\FinanceReportService;
use App\Modules\Roles\Services\PermissionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * CI Financereports slice 1: hub + balance fees report + fees statement
 * + due fees statement/print + daily collection/deposit drill-down.
 */
class FinanceReportController extends Controller
{
    public function __construct(
        protected PermissionService $permissions,
        protected FinanceReportService $reports,
    ) {
    }

    public function finance(): View
    {
        abort_unless($this->canOpenHub(), 403);

        return view('shared::layouts.admin', array_merge([
            'title' => __('system.finance'),
            'contentView' => 'reports::admin.finance.hub',
        ], $this->navFlags()));
    }

    public function studentacademicreport(Request $request): View
    {
        abort_unless($this->permissions->hasPrivilege('balance_fees_report', 'can_view'), 403);

        $filters = [
            'class_id' => $request->input('class_id', ''),
            'section_id' => $request->input('section_id', ''),
            'search_type' => $request->input('search_type', 'all'),
        ];
        $rows = [];
        $searched = false;

        if ($request->isMethod('post')) {
            $request->validate([
                'search_type' => ['required'],
            ], [
                'search_type.required' => 'The Search Type field is required.',
            ]);
            $searched = true;
            $rows = $this->reports->balanceFeesReport(
                $request->filled('class_id') ? (int) $request->input('class_id') : null,
                $request->filled('section_id') ? (int) $request->input('section_id') : null,
                (string) $request->input('search_type')
            );
        }

        return view('shared::layouts.admin', array_merge([
            'title' => __('system.balance_fees_report'),
            'contentView' => 'reports::admin.finance.balance_fees_report',
            'classes' => $this->reports->classes(),
            'payment_type' => $this->reports->paymentSearchTypes(),
            'filters' => $filters,
            'resultarray' => $searched ? [['result' => $rows]] : [],
            'student_due_fee' => $rows,
            'searched' => $searched,
            'reports' => $this->reports,
            'show_roll_no' => $this->reports->settingOn('roll_no'),
            'show_father_name' => $this->reports->settingOn('father_name'),
        ], $this->navFlags()));
    }

    public function reportbyname(Request $request): View
    {
        abort_unless($this->permissions->hasPrivilege('fees_statement', 'can_view'), 403);

        $filters = [
            'class_id' => $request->input('class_id', ''),
            'section_id' => $request->input('section_id', ''),
            'student_id' => $request->input('student_id', ''),
        ];
        $studentDueFee = [];
        $searched = $request->isMethod('post');

        if ($searched) {
            $studentDueFee = $this->reports->feesStatement(
                $request->filled('class_id') ? (int) $request->input('class_id') : null,
                $request->filled('section_id') ? (int) $request->input('section_id') : null,
                $request->filled('student_id') ? (int) $request->input('student_id') : null
            );
        }

        return view('shared::layouts.admin', array_merge([
            'title' => __('system.fees_statement'),
            'contentView' => 'reports::admin.finance.fees_statement',
            'classes' => $this->reports->classes(),
            'filters' => $filters,
            'student_due_fee' => $studentDueFee,
            'searched' => $searched,
            'reports' => $this->reports,
        ], $this->navFlags()));
    }

    public function reportduefees(Request $request): View
    {
        abort_unless($this->permissions->hasPrivilege('balance_fees_statement', 'can_view'), 403);

        $filters = [
            'class_id' => $request->input('class_id', ''),
            'section_id' => $request->input('section_id', ''),
        ];
        $studentDueFee = [];
        $searched = $request->isMethod('post');

        if ($searched) {
            $studentDueFee = $this->reports->dueFeesStatement(
                $request->filled('class_id') ? (int) $request->input('class_id') : null,
                $request->filled('section_id') ? (int) $request->input('section_id') : null
            );
        }

        return view('shared::layouts.admin', array_merge([
            'title' => __('system.balance_fees_statement'),
            'contentView' => 'reports::admin.finance.due_fees_statement',
            'classes' => $this->reports->classes(),
            'filters' => $filters,
            'student_due_fee' => $studentDueFee,
            'searched' => $searched,
            'reports' => $this->reports,
        ], $this->navFlags()));
    }

    public function printreportduefees(Request $request): JsonResponse
    {
        abort_unless($this->permissions->hasPrivilege('balance_fees_statement', 'can_view'), 403);

        $studentDueFee = $this->reports->dueFeesStatement(
            $request->filled('class_id') ? (int) $request->input('class_id') : null,
            $request->filled('section_id') ? (int) $request->input('section_id') : null
        );

        $page = view('reports::admin.finance._print_due_fees', [
            'student_due_fee' => $studentDueFee,
            'reports' => $this->reports,
        ])->render();

        return response()->json(['status' => 1, 'page' => $page]);
    }

    public function reportdailycollection(Request $request): View
    {
        abort_unless($this->permissions->hasPrivilege('daily_collection_report', 'can_view'), 403);

        $filters = [
            'date_from' => $request->input('date_from', ''),
            'date_to' => $request->input('date_to', ''),
        ];
        $feesData = null;
        $searched = false;

        if ($request->isMethod('post')) {
            $request->validate([
                'date_from' => ['required'],
                'date_to' => ['required'],
            ], [
                'date_from.required' => 'The Date From field is required.',
                'date_to.required' => 'The Date To field is required.',
            ]);
            $from = $this->reports->parseDate($request->input('date_from'));
            $to = $this->reports->parseDate($request->input('date_to'));
            abort_if($from === null || $to === null, 422);
            $searched = true;
            $feesData = $this->reports->dailyCollection($from, $to);
            $filters['date_from'] = $request->input('date_from');
            $filters['date_to'] = $request->input('date_to');
        }

        return view('shared::layouts.admin', array_merge([
            'title' => __('system.daily_collection_report'),
            'contentView' => 'reports::admin.finance.daily_collection',
            'filters' => $filters,
            'fees_data' => $feesData,
            'searched' => $searched,
            'reports' => $this->reports,
        ], $this->navFlags()));
    }

    public function feeCollectionStudentDeposit(Request $request): JsonResponse
    {
        abort_unless($this->permissions->hasPrivilege('daily_collection_report', 'can_view'), 403);

        $feesId = (string) $request->input('fees_id', '');
        $ids = $feesId === '' ? [] : explode(',', $feesId);
        $studentList = $this->reports->feesDepositeByIds($ids);

        $page = view('reports::admin.finance._fee_collection_deposit', [
            'student_list' => $studentList,
            'date' => $request->input('date'),
            'reports' => $this->reports,
        ])->render();

        return response()->json(['status' => 1, 'page' => $page]);
    }

    protected function canOpenHub(): bool
    {
        return $this->permissions->hasPrivilege('balance_fees_statement', 'can_view')
            || $this->permissions->hasPrivilege('daily_collection_report', 'can_view')
            || $this->permissions->hasPrivilege('fees_statement', 'can_view')
            || $this->permissions->hasPrivilege('balance_fees_report', 'can_view')
            || $this->permissions->hasPrivilege('fees_collection_report', 'can_view')
            || $this->permissions->hasPrivilege('online_fees_collection_report', 'can_view')
            || $this->permissions->hasPrivilege('balance_fees_report_with_remark', 'can_view')
            || $this->permissions->hasPrivilege('income_report', 'can_view')
            || $this->permissions->hasPrivilege('expense_report', 'can_view')
            || $this->permissions->hasPrivilege('payroll_report', 'can_view')
            || $this->permissions->hasPrivilege('income_group_report', 'can_view')
            || $this->permissions->hasPrivilege('expense_group_report', 'can_view')
            || $this->permissions->hasPrivilege('online_admission_fees_collection_report', 'can_view')
            || $this->permissions->hasPrivilege('due_fees_report', 'can_view')
            || $this->permissions->hasPrivilege('income_expense_balance_report', 'can_view');
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
