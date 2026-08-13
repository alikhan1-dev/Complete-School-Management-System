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
            'message' => 'Admin Homework CRUD (list/create/edit/delete/download) done. Deferred: evaluation, student portal submit, daily assignment, reports, mail/SMS.',
            'slices' => [
                'admin_list_filter' => 'done',
                'admin_crud' => 'done',
                'admin_document_upload_download' => 'done',
                'evaluation' => 'deferred',
                'student_portal_submit' => 'deferred',
                'daily_assignment' => 'deferred',
                'reports' => 'deferred',
                'publish_mail_sms' => 'deferred',
            ],
        ]);
    }
}
