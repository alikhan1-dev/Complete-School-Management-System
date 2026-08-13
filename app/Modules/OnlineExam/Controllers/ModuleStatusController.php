<?php

namespace App\Modules\OnlineExam\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

/**
 * Phase 5 OnlineExam migration status.
 */
class ModuleStatusController extends Controller
{
    public function status(): JsonResponse
    {
        return response()->json([
            'module' => 'OnlineExam',
            'status' => 'in_progress',
            'message' => 'Admin OnlineExam core done. Student portal take-exam (objective) first slice done. Deferred: descriptive uploads, ranking, reports, mail/SMS.',
            'slices' => [
                'question_bank' => 'done',
                'question_csv_import' => 'deferred',
                'question_cms_images' => 'deferred',
                'online_exam_crud' => 'done',
                'attach_questions' => 'done',
                'assign_students' => 'done',
                'evaluation_results' => 'done',
                'student_portal_list_view' => 'done',
                'student_portal_take_exam_objective' => 'done',
                'student_portal_descriptive_upload' => 'deferred',
                'ranking' => 'deferred',
                'reports' => 'deferred',
                'publish_mail_sms' => 'deferred',
            ],
        ]);
    }
}
