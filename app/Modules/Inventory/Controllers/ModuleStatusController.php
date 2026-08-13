<?php

namespace App\Modules\Inventory\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

/**
 * Phase 6 Inventory migration status.
 */
class ModuleStatusController extends Controller
{
    public function status(): JsonResponse
    {
        return response()->json([
            'module' => 'Inventory',
            'status' => 'done',
            'message' => 'Inventory masters, items, stock, issue, and reports done.',
            'slices' => [
                'item_category' => 'done',
                'item_store' => 'done',
                'item_supplier' => 'done',
                'items' => 'done',
                'item_stock' => 'done',
                'issue_item' => 'done',
                'reports' => 'done',
            ],
        ]);
    }
}
