<?php

namespace App\Modules\Payroll\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

/**
 * Phase 6 Payroll migration status.
 */
class ModuleStatusController extends Controller
{
    public function status(): JsonResponse
    {
        return response()->json([
            'module' => 'Payroll',
            'status' => 'done',
            'message' => 'Payroll admin core + report done. Deferred: currency helpers, payslip print header image, SMS/mail, superadmin_visible filter.',
            'slices' => [
                'staff_list_search' => 'done',
                'generate_payslip' => 'done',
                'edit_payslip' => 'done',
                'proceed_to_pay' => 'done',
                'view_payslip' => 'done',
                'delete_revert' => 'done',
                'payroll_report' => 'done',
            ],
        ]);
    }
}
