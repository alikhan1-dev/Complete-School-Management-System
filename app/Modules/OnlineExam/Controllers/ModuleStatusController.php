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
            'message' => 'Admin OnlineExam core done. Student portal take-exam done. Online exam reports incl. class-teacher scope + ranking generation persist done. Question bank superadmin_visible creator masking done. Deferred: mail/SMS, print, SaaS storage quota.',
            'slices' => [
                'question_bank' => 'done',
                'question_bank_superadmin_visible' => 'done',
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
                'online_exam_reports_class_teacher' => 'done',
                'ranking' => 'deferred',
                'reports' => 'done',
                'publish_mail_sms' => 'deferred',
            ],
        ]);
    }
}
