<?php

namespace App\Modules\Finance\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

/**
 * Phase 4 Finance migration status.
 */
class ModuleStatusController extends Controller
{
    public function status(): JsonResponse
    {
        return response()->json([
            'module' => 'Finance',
            'status' => 'operational_core_done',
            'message' => 'Income/expense heads + income/expense CRUD with documents + search_income/search_expense done. Finance reports owned by Reports module (already largely migrated).',
            'slices' => [
                'income_heads' => 'done',
                'expense_heads' => 'done',
                'income' => 'done',
                'expense' => 'done',
                'search_screens' => 'done',
                'reports' => 'moved_to_reports',
            ],
        ]);
    }
}
