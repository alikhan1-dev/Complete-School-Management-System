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
            'message' => 'Admin OnlineExam core done through results/evaluation. Deferred: ranking, reports, mail/SMS, student take-exam portal.',
            'slices' => [
                'question_bank' => 'done',
                'question_csv_import' => 'deferred',
                'question_cms_images' => 'deferred',
                'online_exam_crud' => 'done',
                'attach_questions' => 'done',
                'assign_students' => 'done',
                'evaluation_results' => 'done',
                'ranking' => 'deferred',
                'reports' => 'deferred',
                'student_portal_take_exam' => 'deferred',
                'publish_mail_sms' => 'deferred',
            ],
        ]);
    }
}
