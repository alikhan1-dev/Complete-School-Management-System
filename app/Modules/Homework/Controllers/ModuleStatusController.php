<?php

namespace App\Modules\Homework\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

/**
 * Phase 6 Homework migration status.
 */
class ModuleStatusController extends Controller
{
    public function status(): JsonResponse
    {
        return response()->json([
            'module' => 'Homework',
            'status' => 'in_progress',
            'message' => 'Admin CRUD + evaluation + student portal + daily assignment + core reports done. Deferred: daily assignment report, mail/SMS.',
            'slices' => [
                'admin_list_filter' => 'done',
                'admin_crud' => 'done',
                'admin_document_upload_download' => 'done',
                'evaluation' => 'done',
                'student_portal_submit' => 'done',
                'daily_assignment' => 'done',
                'reports_hub' => 'done',
                'homework_report' => 'done',
                'evaluation_report' => 'done',
                'marks_report' => 'done',
                'daily_assignment_report' => 'deferred',
                'publish_mail_sms' => 'deferred',
            ],
        ]);
    }
}
