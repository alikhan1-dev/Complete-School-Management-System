<?php

namespace App\Modules\Reports\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Reports\Services\FinanceReportService;
use App\Modules\Roles\Services\PermissionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * CI Financereports: hub + fee reports + remark/payroll/admission + income/expense (+ balance).
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

    public function collection_report(Request $request): View
    {
        // Hub privilege (CI method checks collect_fees — menu uses fees_collection_report).
        abort_unless($this->permissions->hasPrivilege('fees_collection_report', 'can_view'), 403);

        $filters = [
            'search_type' => $request->input('search_type', ''),
            'class_id' => $request->input('class_id', ''),
            'section_id' => $request->input('section_id', ''),
            'feetype_id' => $request->input('feetype_id', ''),
            'collect_by' => $request->input('collect_by', ''),
            'group' => $request->input('group', ''),
            'date_from' => $request->input('date_from', ''),
            'date_to' => $request->input('date_to', ''),
        ];
        $results = [];
        $subtotal = false;
        $searched = false;

        if ($request->isMethod('post')) {
            $request->validate([
                'search_type' => ['required'],
            ], [
                'search_type.required' => 'The Search Duration field is required.',
            ]);
            $searched = true;
            $range = $this->reports->dateRange(
                (string) $filters['search_type'],
                $filters['date_from'] !== '' ? (string) $filters['date_from'] : null,
                $filters['date_to'] !== '' ? (string) $filters['date_to'] : null
            );
            $group = (string) $filters['group'];
            $subtotal = $group !== '';
            $rows = $this->reports->feeCollectionReport(
                $range['from'],
                $range['to'],
                $filters['feetype_id'] !== '' ? (int) $filters['feetype_id'] : null,
                $filters['collect_by'] !== '' ? (int) $filters['collect_by'] : null,
                $filters['class_id'] !== '' ? (int) $filters['class_id'] : null,
                $filters['section_id'] !== '' ? (int) $filters['section_id'] : null
            );
            $results = $this->reports->groupCollectionRows($rows, $group);
        }

        return view('shared::layouts.admin', array_merge([
            'title' => __('system.fees_collection_report'),
            'contentView' => 'reports::admin.finance.collection_report',
            'filters' => $filters,
            'results' => $results,
            'subtotal' => $subtotal,
            'searched' => $searched,
            'searchlist' => $this->reports->searchDurationTypes(),
            'group_by' => $this->reports->collectionGroupBy(),
            'collect_by_list' => $this->reports->feesCollectors(),
            'feetypeList' => $this->reports->feeTypes(),
            'classlist' => $this->reports->classes(),
            'reports' => $this->reports,
        ], $this->navFlags()));
    }

    public function onlinefees_report(Request $request): View
    {
        // Hub privilege (CI onlinefees_report has no rbac check).
        abort_unless($this->permissions->hasPrivilege('online_fees_collection_report', 'can_view'), 403);

        $filters = [
            'search_type' => $request->input('search_type', ''),
            'date_from' => $request->input('date_from', ''),
            'date_to' => $request->input('date_to', ''),
        ];
        $collectlist = [];
        $searched = false;

        if ($request->isMethod('post')) {
            $request->validate([
                'search_type' => ['required'],
            ], [
                'search_type.required' => 'The Search Type field is required.',
            ]);
            $searched = true;
            $range = $this->reports->dateRange(
                (string) $filters['search_type'],
                $filters['date_from'] !== '' ? (string) $filters['date_from'] : null,
                $filters['date_to'] !== '' ? (string) $filters['date_to'] : null
            );
            $collectlist = $this->reports->onlineFeeCollectionReport($range['from'], $range['to']);
        }

        return view('shared::layouts.admin', array_merge([
            'title' => __('system.online_fees_report'),
            'contentView' => 'reports::admin.finance.online_fees_report',
            'filters' => $filters,
            'collectlist' => $collectlist,
            'searched' => $searched,
            'searchlist' => $this->reports->searchDurationTypes(),
            'reports' => $this->reports,
        ], $this->navFlags()));
    }

    public function duefeesremark(Request $request): View
    {
        abort_unless($this->permissions->hasPrivilege('balance_fees_report_with_remark', 'can_view'), 403);

        $filters = [
            'class_id' => $request->input('class_id', ''),
            'section_id' => $request->input('section_id', ''),
        ];
        $studentRemainFees = null;
        $searched = false;

        if ($request->isMethod('post')) {
            $request->validate([
                'class_id' => ['required'],
                'section_id' => ['required'],
            ], [
                'class_id.required' => 'The Class field is required.',
                'section_id.required' => 'The Section field is required.',
            ]);
            $searched = true;
            $studentRemainFees = $this->reports->dueFeesWithRemark(
                (int) $filters['class_id'],
                (int) $filters['section_id']
            );
        }

        return view('shared::layouts.admin', array_merge([
            'title' => __('system.balance_fees_report_with_remark'),
            'contentView' => 'reports::admin.finance.due_fees_remark',
            'filters' => $filters,
            'student_remain_fees' => $studentRemainFees,
            'searched' => $searched,
            'classlist' => $this->reports->classes(),
            'reports' => $this->reports,
        ], $this->navFlags()));
    }

    public function printduefeesremark(Request $request): JsonResponse
    {
        // CI quirk: print checks fees_statement, not balance_fees_report_with_remark.
        abort_unless($this->permissions->hasPrivilege('fees_statement', 'can_view'), 403);

        $classId = (int) $request->input('class_id');
        $sectionId = (int) $request->input('section_id');
        $students = $this->reports->dueFeesWithRemark($classId, $sectionId);
        $page = view('reports::admin.finance._print_due_fees_remark', [
            'student_remain_fees' => $students,
            'class' => $this->reports->classLabel($classId),
            'section' => $this->reports->sectionLabel($sectionId),
            'reports' => $this->reports,
        ])->render();

        return response()->json(['status' => 1, 'page' => $page]);
    }

    public function payroll(Request $request): View
    {
        abort_unless($this->permissions->hasPrivilege('payroll_report', 'can_view'), 403);

        $filters = [
            'search_type' => $request->input('search_type', ''),
            'date_from' => $request->input('date_from', ''),
            'date_to' => $request->input('date_to', ''),
        ];
        // CI: empty search_type still loads this_year range (even on GET).
        $searchType = $filters['search_type'] !== '' ? (string) $filters['search_type'] : 'this_year';
        $range = $this->reports->dateRange(
            $searchType,
            $filters['date_from'] !== '' ? (string) $filters['date_from'] : null,
            $filters['date_to'] !== '' ? (string) $filters['date_to'] : null
        );
        $payrollList = $this->reports->betweenPayrollReport($range['from'], $range['to']);
        $label = $this->reports->formatDate($range['from']).' '.__('system.to').' '.$this->reports->formatDate($range['to']);

        return view('shared::layouts.admin', array_merge([
            'title' => __('system.payroll_report'),
            'contentView' => 'reports::admin.finance.payroll',
            'filters' => $filters,
            'payrollList' => $payrollList,
            'label' => $label,
            'searchlist' => $this->reports->searchDurationTypes(),
            'reports' => $this->reports,
        ], $this->navFlags()));
    }

    public function onlineadmission(Request $request): View
    {
        // Hub privilege (CI controller checks online_admission).
        abort_unless($this->permissions->hasPrivilege('online_admission_fees_collection_report', 'can_view'), 403);

        $filters = [
            'search_type' => $request->input('search_type', ''),
            'date_from' => $request->input('date_from', ''),
            'date_to' => $request->input('date_to', ''),
        ];
        $collectlist = [];
        $searched = false;

        if ($request->isMethod('post')) {
            $request->validate([
                'search_type' => ['required'],
            ], [
                'search_type.required' => 'The Search Type field is required.',
            ]);
            $searched = true;
            $range = $this->reports->dateRange(
                (string) $filters['search_type'],
                $filters['date_from'] !== '' ? (string) $filters['date_from'] : null,
                $filters['date_to'] !== '' ? (string) $filters['date_to'] : null
            );
            $collectlist = $this->reports->onlineAdmissionFeeCollectionReport($range['from'], $range['to']);
        }

        return view('shared::layouts.admin', array_merge([
            'title' => __('system.online_admission_fees_collection_report'),
            'contentView' => 'reports::admin.finance.online_admission',
            'filters' => $filters,
            'collectlist' => $collectlist,
            'searched' => $searched,
            'searchlist' => $this->reports->searchDurationTypes(),
            'reports' => $this->reports,
        ], $this->navFlags()));
    }

    public function incomeexpensebalancereport(Request $request): View
    {
        abort_unless($this->permissions->hasPrivilege('income_expense_balance_report', 'can_view'), 403);

        $filters = [
            'search_type' => $request->input('search_type', ''),
            'date_from' => $request->input('date_from', ''),
            'date_to' => $request->input('date_to', ''),
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
            $range = $this->reports->dateRange(
                (string) $filters['search_type'],
                $filters['date_from'] !== '' ? (string) $filters['date_from'] : null,
                $filters['date_to'] !== '' ? (string) $filters['date_to'] : null
            );
            $rows = $this->reports->incomeExpenseBalanceReport($range['from'], $range['to']);
        }

        return view('shared::layouts.admin', array_merge([
            'title' => __('system.income_expense_balance_report'),
            'contentView' => 'reports::admin.finance.income_expense_balance',
            'filters' => $filters,
            'incomeexpensebalancereport' => $rows,
            'searched' => $searched,
            'searchlist' => $this->reports->searchDurationTypes(),
            'reports' => $this->reports,
        ], $this->navFlags()));
    }

    public function income(Request $request): View
    {
        abort_unless($this->permissions->hasPrivilege('income_report', 'can_view'), 403);

        $filters = [
            'search_type' => $request->input('search_type', ''),
            'date_from' => $request->input('date_from', ''),
            'date_to' => $request->input('date_to', ''),
        ];
        $incomeList = [];
        $searched = false;

        if ($request->isMethod('post')) {
            $request->validate([
                'search_type' => ['required'],
            ], [
                'search_type.required' => 'The Search Type field is required.',
            ]);
            $searched = true;
            $range = $this->reports->dateRange(
                (string) $filters['search_type'],
                $filters['date_from'] !== '' ? (string) $filters['date_from'] : null,
                $filters['date_to'] !== '' ? (string) $filters['date_to'] : null
            );
            $incomeList = $this->reports->incomeReport($range['from'], $range['to']);
        }

        return view('shared::layouts.admin', array_merge([
            'title' => __('system.income_report'),
            'contentView' => 'reports::admin.finance.income',
            'filters' => $filters,
            'incomeList' => $incomeList,
            'searched' => $searched,
            'searchlist' => $this->reports->searchDurationTypes(),
            'reports' => $this->reports,
        ], $this->navFlags()));
    }

    public function expense(Request $request): View
    {
        abort_unless($this->permissions->hasPrivilege('expense_report', 'can_view'), 403);

        $filters = [
            'search_type' => $request->input('search_type', ''),
            'date_from' => $request->input('date_from', ''),
            'date_to' => $request->input('date_to', ''),
        ];
        $expenseList = [];
        $searched = false;

        if ($request->isMethod('post')) {
            $request->validate([
                'search_type' => ['required'],
            ], [
                'search_type.required' => 'The Search Type field is required.',
            ]);
            $searched = true;
            $range = $this->reports->dateRange(
                (string) $filters['search_type'],
                $filters['date_from'] !== '' ? (string) $filters['date_from'] : null,
                $filters['date_to'] !== '' ? (string) $filters['date_to'] : null
            );
            $expenseList = $this->reports->expenseReport($range['from'], $range['to']);
        }

        return view('shared::layouts.admin', array_merge([
            'title' => __('system.expense_report'),
            'contentView' => 'reports::admin.finance.expense',
            'filters' => $filters,
            'expenseList' => $expenseList,
            'searched' => $searched,
            'searchlist' => $this->reports->searchDurationTypes(),
            'reports' => $this->reports,
        ], $this->navFlags()));
    }

    public function incomegroup(Request $request): View
    {
        abort_unless($this->permissions->hasPrivilege('income_group_report', 'can_view'), 403);

        $filters = [
            'search_type' => $request->input('search_type', ''),
            'date_from' => $request->input('date_from', ''),
            'date_to' => $request->input('date_to', ''),
            'head' => $request->input('head', ''),
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
            $range = $this->reports->dateRange(
                (string) $filters['search_type'],
                $filters['date_from'] !== '' ? (string) $filters['date_from'] : null,
                $filters['date_to'] !== '' ? (string) $filters['date_to'] : null
            );
            $rows = $this->reports->incomeGroupReport(
                $range['from'],
                $range['to'],
                $filters['head'] !== '' ? (int) $filters['head'] : null
            );
        }

        return view('shared::layouts.admin', array_merge([
            'title' => __('system.income_group_report'),
            'contentView' => 'reports::admin.finance.income_group',
            'filters' => $filters,
            'rows' => $rows,
            'searched' => $searched,
            'searchlist' => $this->reports->searchDurationTypes(),
            'headlist' => $this->reports->incomeHeads(),
            'reports' => $this->reports,
        ], $this->navFlags()));
    }

    public function expensegroup(Request $request): View
    {
        abort_unless($this->permissions->hasPrivilege('expense_group_report', 'can_view'), 403);

        $filters = [
            'search_type' => $request->input('search_type', ''),
            'date_from' => $request->input('date_from', ''),
            'date_to' => $request->input('date_to', ''),
            'head' => $request->input('head', ''),
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
            $range = $this->reports->dateRange(
                (string) $filters['search_type'],
                $filters['date_from'] !== '' ? (string) $filters['date_from'] : null,
                $filters['date_to'] !== '' ? (string) $filters['date_to'] : null
            );
            $rows = $this->reports->expenseGroupReport(
                $range['from'],
                $range['to'],
                $filters['head'] !== '' ? (int) $filters['head'] : null
            );
        }

        return view('shared::layouts.admin', array_merge([
            'title' => __('system.expense_group_report'),
            'contentView' => 'reports::admin.finance.expense_group',
            'filters' => $filters,
            'rows' => $rows,
            'searched' => $searched,
            'searchlist' => $this->reports->searchDurationTypes(),
            'headlist' => $this->reports->expenseHeads(),
            'reports' => $this->reports,
        ], $this->navFlags()));
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
