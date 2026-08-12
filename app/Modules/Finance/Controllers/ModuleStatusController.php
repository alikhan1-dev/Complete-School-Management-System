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
            'status' => 'in_progress',
            'message' => 'Income/expense heads + income/expense CRUD with documents done. Deferred: search_income/search_expense date screens, finance reports.',
            'slices' => [
                'income_heads' => 'done',
                'expense_heads' => 'done',
                'income' => 'done',
                'expense' => 'done',
                'search_screens' => 'deferred',
                'reports' => 'deferred',
            ],
        ]);
    }
}
