<?php

namespace App\Modules\Library\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

/**
 * Phase 6 Library migration status.
 */
class ModuleStatusController extends Controller
{
    public function status(): JsonResponse
    {
        return response()->json([
            'module' => 'Library',
            'status' => 'in_progress',
            'message' => 'Books CRUD + import + members + issue/return done. Deferred: reports, portal.',
            'slices' => [
                'books_crud' => 'done',
                'books_import' => 'done',
                'members_student_staff' => 'done',
                'issue_return' => 'done',
                'reports' => 'deferred',
                'student_portal' => 'deferred',
            ],
        ]);
    }
}
