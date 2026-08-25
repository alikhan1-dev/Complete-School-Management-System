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
            'message' => 'Homework module reports complete (incl. homework report class-teacher scope). Deferred: mail/SMS (Communication module).',
            'slices' => [
                'admin_list_filter' => 'done',
                'admin_crud' => 'done',
                'admin_document_upload_download' => 'done',
                'evaluation' => 'done',
                'student_portal_submit' => 'done',
                'daily_assignment' => 'done',
                'reports_hub' => 'done',
                'homework_report' => 'done',
                'homework_report_class_teacher' => 'done',
                'evaluation_report' => 'done',
                'marks_report' => 'done',
                'daily_assignment_report' => 'done',
                'publish_mail_sms' => 'deferred_to_communication',
            ],
        ]);
    }
}
