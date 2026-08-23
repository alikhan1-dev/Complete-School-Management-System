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
            'message' => 'Phase 3 Fees operational core complete: types/groups/master/assign/discounts/collect/multi/due-fees/carry-forward + transport single collect. Deferred: transport multi-collect, print/SMS.',
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
                'collect_transport_multi' => 'deferred',
            ],
        ]);
    }
}
