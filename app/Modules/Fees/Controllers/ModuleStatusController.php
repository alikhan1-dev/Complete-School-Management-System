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
            'message' => 'Slices 1–5 core done: types, groups, master, assign, discounts, collect (deposit). Deferred: transport, multi-collect, print/SMS, due-fees report, carry-forward.',
            'slices' => [
                'fee_types' => 'done',
                'fee_groups' => 'done',
                'fee_master' => 'done',
                'assign' => 'done',
                'discounts' => 'done',
                'collect' => 'done',
                'collect_transport' => 'pending',
                'collect_multi' => 'pending',
                'fees_carry_forward' => 'pending',
            ],
        ]);
    }
}
