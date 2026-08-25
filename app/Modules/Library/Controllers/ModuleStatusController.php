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
            'status' => 'done',
            'message' => 'Library admin + reports + student portal done (incl. book issue report class-teacher empty-matrix deny). Deferred: admin issue_report list, superadmin_visible filtering.',
            'slices' => [
                'books_crud' => 'done',
                'books_import' => 'done',
                'members_student_staff' => 'done',
                'issue_return' => 'done',
                'reports' => 'done',
                'book_issue_report_class_teacher' => 'done',
                'student_portal' => 'done',
            ],
        ]);
    }
}
