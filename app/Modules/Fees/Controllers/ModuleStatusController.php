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
            'message' => 'Phase 3 Fees operational core complete: types/groups/master/assign/discounts/collect/multi/due-fees/carry-forward + transport single collect + offline bank payments (admin + portal submit). Deferred: transport multi-collect, student getfees ledger UI, print/SMS.',
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
                'offline_bank_payments' => 'done',
                'offline_bank_payments_portal' => 'done',
                'student_getfees_ledger' => 'deferred',
                'collect_transport_multi' => 'deferred',
            ],
        ]);
    }
}
