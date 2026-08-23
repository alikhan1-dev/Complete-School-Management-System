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
            'status' => 'in_progress',
            'message' => 'Phase 3 Fees operational core complete: types/groups/master/assign/discounts/collect/multi/due-fees/carry-forward + transport collect (single+multi) + offline bank payments + student getfees portal ledger. Deferred: online gateway pay modal, print/SMS, transport fees-master admin.',
            'slices' => [
                'fee_types' => 'done',
                'fee_groups' => 'done',
                'fee_master' => 'done',
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
                'student_getfees_online_pay' => 'deferred',
            ],
        ]);
    }
}
