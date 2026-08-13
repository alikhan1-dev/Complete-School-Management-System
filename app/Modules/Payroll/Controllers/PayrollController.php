<?php

namespace App\Modules\Payroll\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Payroll\Services\PayrollService;
use App\Modules\Roles\Services\PermissionService;
use App\Modules\Settings\Models\SchSetting;
use App\Modules\Shared\Services\SchoolContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * CI admin/Payroll — staff payroll list, generate, edit, pay, view, delete/revert.
 * Prefer form pages over CI AJAX modals for proceed-to-pay and payslip view.
 */
class PayrollController extends Controller
{
    public function __construct(
        protected PermissionService $permissions,
        protected PayrollService $payroll,
        protected SchoolContext $school,
    ) {
    }

    public function index(Request $request): View
    {
        abort_unless($this->permissions->hasPrivilege('staff_payroll', 'can_view'), 403);

        $month = date('F', strtotime('-1 month'));
        $year = date('Y');
        $role = '';
        $resultlist = null;

        if ($request->isMethod('post') && $request->input('search') === 'search') {
            $month = (string) $request->input('month', $month);
            $year = (string) $request->input('year', $year);
            $role = (string) $request->input('role', '');
            $empName = (string) $request->input('name', '');
            $resultlist = $this->payroll->searchEmployee($month, $year, $empName, $role);
        }

        return $this->staffListView($month, $year, $role, $resultlist);
    }

    /**
     * CI admin/payroll/search/{month}/{year}/{role?} — after delete/revert.
     */
    public function search(string $month, string $year, string $role = ''): View
    {
        abort_unless($this->permissions->hasPrivilege('staff_payroll', 'can_view'), 403);

        $role = $role === '' ? '' : urldecode($role);
        $resultlist = $this->payroll->searchEmployee($month, $year, '', $role);

        return $this->staffListView($month, $year, $role, $resultlist);
    }

    public function create(string $month, string $year, int $id): View
    {
        abort_unless($this->permissions->hasPrivilege('staff_payroll', 'can_add'), 403);

        $ctx = $this->payroll->createFormContext($month, $year, $id);

        return view('shared::layouts.admin', [
            'title' => 'Generate Payroll',
            'contentView' => 'payroll::admin.create',
            'schSetting' => SchSetting::query()->first(),
            'currencySymbol' => $this->school->currencySymbol(),
            'canAdd' => true,
            ...$ctx,
        ]);
    }

    public function payslip(Request $request): RedirectResponse|View
    {
        abort_unless($this->permissions->hasPrivilege('staff_payroll', 'can_add'), 403);

        $validated = $request->validate([
            'net_salary' => ['required'],
            'staff_id' => ['required', 'integer'],
            'month' => ['required', 'string'],
            'year' => ['required', 'string'],
            'status' => ['nullable', 'string'],
            'basic' => ['nullable'],
            'total_allowance' => ['nullable'],
            'total_deduction' => ['nullable'],
            'tax' => ['nullable'],
            'allowance_type' => ['nullable', 'array'],
            'allowance_amount' => ['nullable', 'array'],
            'deduction_type' => ['nullable', 'array'],
            'deduction_amount' => ['nullable', 'array'],
        ]);

        $staffId = (int) $validated['staff_id'];
        $month = (string) $validated['month'];
        $year = (string) $validated['year'];

        if (! $this->payroll->checkPayslipAvailable($month, $year, $staffId)) {
            return redirect()
                ->route('payroll.index')
                ->with('warning', 'Payslip already generated.');
        }

        $payslipId = $this->payroll->createPayslip([
            'staff_id' => $staffId,
            'basic' => $this->payroll->toAmount($validated['basic'] ?? 0),
            'total_allowance' => $this->payroll->toAmount($validated['total_allowance'] ?? 0),
            'total_deduction' => $this->payroll->toAmount($validated['total_deduction'] ?? 0),
            'net_salary' => $this->payroll->toAmount($validated['net_salary']),
            'payment_date' => date('Y-m-d'),
            'status' => (string) ($validated['status'] ?? 'generated'),
            'month' => $month,
            'year' => $year,
            'tax' => (string) $this->payroll->toAmount($validated['tax'] ?? 0),
            'leave_deduction' => 0,
            'generated_by' => $this->payroll->generatedByStaffId(),
        ]);

        foreach ($this->payroll->zipAllowanceLines(
            $request->input('allowance_type'),
            $request->input('allowance_amount')
        ) as $line) {
            if ($line['type'] === '' && $line['amount'] == 0.0) {
                continue;
            }
            $this->payroll->addAllowance([
                'payslip_id' => $payslipId,
                'allowance_type' => $line['type'] !== '' ? $line['type'] : 'Earning',
                'amount' => $line['amount'],
                'staff_id' => $staffId,
                'cal_type' => 'positive',
            ]);
        }

        foreach ($this->payroll->zipAllowanceLines(
            $request->input('deduction_type'),
            $request->input('deduction_amount')
        ) as $line) {
            if ($line['type'] === '' && $line['amount'] == 0.0) {
                continue;
            }
            $this->payroll->addAllowance([
                'payslip_id' => $payslipId,
                'allowance_type' => $line['type'] !== '' ? $line['type'] : 'Deduction',
                'amount' => $line['amount'],
                'staff_id' => $staffId,
                'cal_type' => 'negative',
            ]);
        }

        return redirect()->route('payroll.index')->with('success', 'Payslip generated successfully.');
    }

    public function edit(int $id): View
    {
        abort_unless($this->permissions->hasPrivilege('staff_payroll', 'can_edit'), 403);

        $employeePayroll = $this->payroll->getPayslip($id);
        abort_if($employeePayroll === null, 404);

        $staffId = (int) $employeePayroll['staff_id'];
        $month = (string) $employeePayroll['month'];
        $year = (string) $employeePayroll['year'];
        $ctx = $this->payroll->createFormContext($month, $year, $staffId);

        return view('shared::layouts.admin', [
            'title' => 'Edit Payroll',
            'contentView' => 'payroll::admin.edit',
            'schSetting' => SchSetting::query()->first(),
            'currencySymbol' => $this->school->currencySymbol(),
            'employee_payroll' => $employeePayroll,
            'earnings' => $this->payroll->getAllowance($id, 'positive'),
            'deductions' => $this->payroll->getAllowance($id, 'negative'),
            'canEdit' => true,
            ...$ctx,
        ]);
    }

    public function editPayroll(Request $request): RedirectResponse
    {
        abort_unless($this->permissions->hasPrivilege('staff_payroll', 'can_edit'), 403);

        $validated = $request->validate([
            'id' => ['required', 'integer'],
            'net_salary' => ['required'],
            'staff_id' => ['required', 'integer'],
            'month' => ['required', 'string'],
            'year' => ['required', 'string'],
            'status' => ['nullable', 'string'],
            'basic' => ['nullable'],
            'total_allowance' => ['nullable'],
            'total_deduction' => ['nullable'],
            'tax' => ['nullable'],
            'allowance_type' => ['nullable', 'array'],
            'allowance_amount' => ['nullable', 'array'],
            'allowance_prev_id' => ['nullable', 'array'],
            'deduction_type' => ['nullable', 'array'],
            'deduction_amount' => ['nullable', 'array'],
            'deduction_prev_id' => ['nullable', 'array'],
        ]);

        $payslipId = (int) $validated['id'];
        $staffId = (int) $validated['staff_id'];
        $month = (string) $validated['month'];
        $year = (string) $validated['year'];

        if ($this->payroll->checkPayslipAvailable($month, $year, $staffId)) {
            return redirect()
                ->route('payroll.index')
                ->with('warning', 'Payslip not generated.');
        }

        $this->payroll->createPayslip([
            'id' => $payslipId,
            'staff_id' => $staffId,
            'basic' => $this->payroll->toAmount($validated['basic'] ?? 0),
            'total_allowance' => $this->payroll->toAmount($validated['total_allowance'] ?? 0),
            'total_deduction' => $this->payroll->toAmount($validated['total_deduction'] ?? 0),
            'net_salary' => $this->payroll->toAmount($validated['net_salary']),
            'payment_date' => date('Y-m-d'),
            'status' => (string) ($validated['status'] ?? 'generated'),
            'month' => $month,
            'year' => $year,
            'tax' => (string) $this->payroll->toAmount($validated['tax'] ?? 0),
            'leave_deduction' => 0,
            'generated_by' => $this->payroll->generatedByStaffId(),
        ]);

        $this->syncAllowanceSide(
            $payslipId,
            $staffId,
            $request->input('allowance_type'),
            $request->input('allowance_amount'),
            $request->input('allowance_prev_id'),
            'positive'
        );
        $this->syncAllowanceSide(
            $payslipId,
            $staffId,
            $request->input('deduction_type'),
            $request->input('deduction_amount'),
            $request->input('deduction_prev_id'),
            'negative'
        );

        return redirect()->route('payroll.index')->with('success', 'Payslip updated successfully.');
    }

    /**
     * Form page instead of CI AJAX proceed-to-pay modal.
     */
    public function payForm(int $staffId, string $month, string $year): View
    {
        abort_unless($this->permissions->hasPrivilege('staff_payroll', 'can_add'), 403);

        $payment = $this->payroll->searchPayment($staffId, $month, $year);
        abort_if($payment === null, 404);

        $monthlist = $this->payroll->monthDropdown();

        return view('shared::layouts.admin', [
            'title' => 'Proceed to Pay',
            'contentView' => 'payroll::admin.pay',
            'payment' => $payment,
            'month' => $month,
            'year' => $year,
            'monthLabel' => $monthlist[$month] ?? $month,
            'paymentModes' => PayrollService::PAYMENT_MODE,
            'currencySymbol' => $this->school->currencySymbol(),
        ]);
    }

    public function paymentSuccess(Request $request): RedirectResponse
    {
        abort_unless($this->permissions->hasPrivilege('staff_payroll', 'can_add'), 403);

        $validated = $request->validate([
            'payment_mode' => ['required', 'string', 'in:cash,cheque,online'],
            'payment_date' => ['required', 'date'],
            'paymentid' => ['required', 'integer'],
            'remarks' => ['nullable', 'string', 'max:200'],
        ]);

        $this->payroll->paymentSuccess([
            'payment_mode' => $validated['payment_mode'],
            'payment_date' => date('Y-m-d', strtotime($validated['payment_date'])),
            'remark' => (string) ($validated['remarks'] ?? ''),
            'status' => 'paid',
        ], (int) $validated['paymentid']);

        return redirect()->route('payroll.index')->with('success', 'Payment recorded successfully.');
    }

    /**
     * CI paymentRecord AJAX — kept for optional clients; primary UI uses payForm.
     */
    public function paymentRecord(Request $request): JsonResponse
    {
        abort_unless($this->permissions->hasPrivilege('staff_payroll', 'can_view'), 403);

        $month = (string) $request->input('month');
        $year = (string) $request->input('year');
        $staffId = (int) $request->input('staffid');
        $searchEmployee = $this->payroll->searchPayment($staffId, $month, $year);
        abort_if($searchEmployee === null, 404);

        $monthlist = $this->payroll->monthDropdown();

        return response()->json([
            'result' => $searchEmployee,
            'net_salary' => number_format((float) $searchEmployee['net_salary'], 2, '.', ''),
            'monthlist' => $monthlist,
            'month' => $monthlist[$month] ?? $month,
            'year' => $year,
        ]);
    }

    public function view(int $id): View
    {
        abort_unless($this->permissions->hasPrivilege('staff_payroll', 'can_view'), 403);

        return $this->payslipViewPage($id);
    }

    /**
     * CI payslipView POST — returns HTML fragment for modal; also supports GET via view().
     */
    public function payslipView(Request $request): View
    {
        abort_unless($this->permissions->hasPrivilege('staff_payroll', 'can_view'), 403);

        $id = (int) $request->input('payslipid', $request->route('id'));
        abort_if($id <= 0, 404);

        return $this->payslipViewPage($id);
    }

    public function deletePayroll(int $payslipid, string $month, string $year, string $role = ''): RedirectResponse
    {
        abort_unless($this->permissions->hasPrivilege('staff_payroll', 'can_delete'), 403);

        $this->payroll->deletePayslip($payslipid);

        return redirect()->to($this->searchUrl($month, $year, $role))
            ->with('success', 'Payslip reverted successfully.');
    }

    public function revertPayroll(int $payslipid, string $month, string $year, string $role = ''): RedirectResponse
    {
        abort_unless($this->permissions->hasPrivilege('staff_payroll', 'can_delete'), 403);

        $this->payroll->revertPayslipStatus($payslipid);

        return redirect()->to($this->searchUrl($month, $year, $role))
            ->with('success', 'Payment reverted to generated.');
    }

    /**
     * @param  list<array<string, mixed>>|null  $resultlist
     */
    protected function staffListView(string $month, string $year, string $role, ?array $resultlist): View
    {
        $schSetting = SchSetting::query()->first();

        return view('shared::layouts.admin', [
            'title' => 'Staff Payroll',
            'contentView' => 'payroll::admin.stafflist',
            'classlist' => $this->payroll->staffRoles(),
            'monthlist' => $this->payroll->monthDropdown(),
            'month' => $month,
            'year' => $year,
            'role_selected' => $role,
            'resultlist' => $resultlist,
            'payroll_status' => PayrollService::PAYROLL_STATUS,
            'payment_mode' => PayrollService::PAYMENT_MODE,
            'schSetting' => $schSetting,
            'currencySymbol' => $this->school->currencySymbol(),
            'canAdd' => $this->permissions->hasPrivilege('staff_payroll', 'can_add'),
            'canEdit' => $this->permissions->hasPrivilege('staff_payroll', 'can_edit'),
            'canDelete' => $this->permissions->hasPrivilege('staff_payroll', 'can_delete'),
        ]);
    }

    protected function payslipViewPage(int $id): View
    {
        $result = $this->payroll->getPayslip($id);
        abort_if($result === null, 404);

        return view('shared::layouts.admin', [
            'title' => 'Payslip',
            'contentView' => 'payroll::admin.payslipview',
            'result' => $result,
            'positive_allowance' => $this->payroll->getAllowance($id, 'positive'),
            'negative_allowance' => $this->payroll->getAllowance($id, 'negative'),
            'payment_mode' => PayrollService::PAYMENT_MODE,
            'schSetting' => SchSetting::query()->first(),
            'currencySymbol' => $this->school->currencySymbol(),
        ]);
    }

    /**
     * @param  list<string|null>|null  $types
     * @param  list<string|null>|null  $amounts
     * @param  list<int|string|null>|null  $prevIds
     */
    protected function syncAllowanceSide(
        int $payslipId,
        int $staffId,
        ?array $types,
        ?array $amounts,
        ?array $prevIds,
        string $calType,
    ): void {
        $insert = [];
        $update = [];
        $keep = [0];

        if ($types !== null && $types !== []) {
            foreach ($types as $i => $type) {
                $prevId = (int) ($prevIds[$i] ?? 0);
                $amount = $this->payroll->toAmount($amounts[$i] ?? 0);
                $allowanceType = (string) ($type ?? '');
                if ($allowanceType === '' && $amount == 0.0 && $prevId === 0) {
                    continue;
                }
                if ($allowanceType === '') {
                    $allowanceType = $calType === 'positive' ? 'Earning' : 'Deduction';
                }
                $row = [
                    'payslip_id' => $payslipId,
                    'allowance_type' => $allowanceType,
                    'amount' => $amount,
                    'staff_id' => $staffId,
                    'cal_type' => $calType,
                ];
                if ($prevId !== 0) {
                    $row['id'] = $prevId;
                    $update[] = $row;
                    $keep[] = $prevId;
                } else {
                    $insert[] = $row;
                }
            }
        }

        $this->payroll->updateAllowance($insert, $update, $keep, $payslipId, $calType);
    }

    protected function searchUrl(string $month, string $year, string $role): string
    {
        $url = 'admin/payroll/search/'.rawurlencode($month).'/'.rawurlencode($year);
        if ($role !== '') {
            $url .= '/'.rawurlencode($role);
        }

        return url($url);
    }
}
