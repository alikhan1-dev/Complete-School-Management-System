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
            'message' => 'Library admin + reports + student portal done (incl. book issue report class-teacher empty-matrix deny + library reports superadmin_visible staff masking + member list superadmin filter + admin issue report list).',
            'slices' => [
                'books_crud' => 'done',
                'books_import' => 'done',
                'members_student_staff' => 'done',
                'library_members_superadmin_visible' => 'done',
                'issue_return' => 'done',
                'library_admin_issue_report' => 'done',
                'reports' => 'done',
                'book_issue_report_class_teacher' => 'done',
                'library_reports_superadmin_visible' => 'done',
                'student_portal' => 'done',
            ],
        ]);
    }
}
