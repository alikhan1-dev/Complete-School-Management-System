<?php

namespace App\Modules\Fees\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

/**
 * Phase 3 Fees migration status.
 */
class ModuleStatusController extends Controller
{
    public function status(): JsonResponse
    {
        return response()->json([
            'module' => 'Fees',
            'status' => 'operational_core_done',
            'message' => 'Phase 3 Fees operational core complete. Deferred cross-module: live gateway charge APIs (Payments), fee_submission/fees_reminder live mail/SMS/WhatsApp (Communication). Transport fees-master admin owned by Transport module.',
            'slices' => [
                'fee_types' => 'done',
                'fee_groups' => 'done',
                'fee_master' => 'done',
                'fee_master_cumulative_fine' => 'done',
                'assign' => 'done',
                'discounts' => 'done',
                'collect' => 'done',
                'collect_multi' => 'done',
                'search_due_fees' => 'done',
                'fees_carry_forward' => 'done',
                'collect_transport' => 'done',
                'collect_transport_multi' => 'done',
                'offline_bank_payments' => 'done',
                'offline_bank_payments_portal' => 'done',
                'student_getfees_ledger' => 'done',
                'print_fees_by_name' => 'done',
                'print_fees_by_group' => 'done',
                'print_fees_by_group_array' => 'done',
                'thermal_print' => 'done',
                'fees_reminder_settings' => 'done',
                'fees_reminder_cron_persist' => 'done',
                'fees_reminder_cron_live_send' => 'deferred',
                'fee_submission_notification_persist' => 'done',
                'fee_submission_live_send' => 'deferred',
                'download_receipt' => 'done',
                'student_getfees_print' => 'done',
                'student_getfees_processing_banner' => 'done',
                'student_getfees_online_pay' => 'done',
                'student_getfees_online_pay_live_gateway' => 'deferred',
                'transport_fees_master_admin' => 'moved_to_transport',
            ],
        ]);
    }
}
