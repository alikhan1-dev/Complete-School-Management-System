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
            'message' => 'Admin OnlineExam core done. Student portal take-exam (objective + descriptive/upload) done. Online examinations report suite (hub/exams/attempt/result/rank) persist done. Deferred: ranking generation UI, mail/SMS, print, SaaS storage quota.',
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
                'student_portal_descriptive_upload' => 'done',
                'online_exams_report_hub' => 'done',
                'ranking' => 'deferred',
                'reports' => 'in_progress',
                'publish_mail_sms' => 'deferred',
            ],
        ]);
    }
}
