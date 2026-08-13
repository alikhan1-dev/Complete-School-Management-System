<?php

namespace App\Modules\Payroll\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Payroll\Services\PayrollService;
use App\Modules\Roles\Services\PermissionService;
use App\Modules\Shared\Services\SchoolContext;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * CI admin/payroll/payrollreport — paid payroll report (privilege payroll_report).
 */
class PayrollReportController extends Controller
{
    public function __construct(
        protected PermissionService $permissions,
        protected PayrollService $payroll,
        protected SchoolContext $school,
    ) {
    }

    public function index(Request $request): View
    {
        abort_unless($this->permissions->hasPrivilege('payroll_report', 'can_view'), 403);

        $month = (string) $request->input('month', '');
        $year = (string) $request->input('year', '');
        $role = (string) $request->input('role', 'select');
        $result = null;

        if ($request->isMethod('post')) {
            $request->validate([
                'year' => ['required', 'string'],
            ]);
            $year = (string) $request->input('year');
            $month = (string) $request->input('month', '');
            $role = (string) $request->input('role', 'select');
            $result = $this->payroll->getPayrollReport($month, $year, $role);
        }

        return view('shared::layouts.admin', [
            'title' => 'Payroll Report',
            'contentView' => 'payroll::admin.payrollreport',
            'month' => $month,
            'year' => $year,
            'role_select' => $role,
            'monthlist' => $this->payroll->monthDropdown(),
            'yearlist' => $this->payroll->payrollYearCount(),
            'role' => $this->payroll->staffRoles(),
            'payment_mode' => PayrollService::PAYMENT_MODE,
            'result' => $result,
            'currencySymbol' => $this->school->currencySymbol(),
        ]);
    }
}
