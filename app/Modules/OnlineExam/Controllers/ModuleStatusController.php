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
            'message' => 'Question bank + online exam CRUD (open/closed) done. Deferred: attach questions, assign students, results, ranking, reports, mail/SMS, student portal.',
            'slices' => [
                'question_bank' => 'done',
                'question_csv_import' => 'deferred',
                'question_cms_images' => 'deferred',
                'online_exam_crud' => 'done',
                'attach_questions' => 'pending',
                'assign_students' => 'pending',
                'evaluation_results' => 'pending',
                'ranking' => 'deferred',
                'reports' => 'deferred',
                'student_portal_take_exam' => 'deferred',
                'publish_mail_sms' => 'deferred',
            ],
        ]);
    }
}
