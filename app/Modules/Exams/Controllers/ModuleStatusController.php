<?php

namespace App\Modules\Exams\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

/**
 * Phase 5 Exams migration status.
 */
class ModuleStatusController extends Controller
{
    public function status(): JsonResponse
    {
        return response()->json([
            'module' => 'Exams',
            'status' => 'in_progress',
            'message' => 'Examgroup core + marksheet/admitcard design templates + HTML print done. Deferred: mPDF/email print, OnlineExam, Certificates.',
            'slices' => [
                'exam_groups' => 'done',
                'exams_in_group' => 'done',
                'exam_subjects' => 'done',
                'assign_students' => 'done',
                'marks_entry' => 'done',
                'link_exams' => 'done',
                'publish_flags' => 'done',
                'marksheet_templates' => 'done',
                'admitcard_templates' => 'done',
                'print_marksheet_html' => 'done',
                'print_admitcard_html' => 'done',
                'print_mpdf_email' => 'deferred',
                'publish_sms_marksheet' => 'deferred',
                'marks_csv_import' => 'deferred',
                'online_exam' => 'pending',
                'certificates' => 'pending',
            ],
        ]);
    }
}
