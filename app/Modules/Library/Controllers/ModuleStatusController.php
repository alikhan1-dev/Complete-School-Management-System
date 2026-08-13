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
            'message' => 'Books CRUD + members enroll/surrender done. Deferred: issue/return, CSV import, reports, portal.',
            'slices' => [
                'books_crud' => 'done',
                'books_import' => 'deferred',
                'members_student_staff' => 'done',
                'issue_return' => 'deferred',
                'reports' => 'deferred',
                'student_portal' => 'deferred',
            ],
        ]);
    }
}
