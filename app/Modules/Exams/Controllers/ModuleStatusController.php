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
            'message' => 'Examgroup core done: groups, exams, subjects, assign, marks, link exams. Publish flags on exam CRUD. Deferred: marksheet/SMS on publish, marksheets/admit cards, marks CSV, OnlineExam, Certificates.',
            'slices' => [
                'exam_groups' => 'done',
                'exams_in_group' => 'done',
                'exam_subjects' => 'done',
                'assign_students' => 'done',
                'marks_entry' => 'done',
                'link_exams' => 'done',
                'publish_flags' => 'done',
                'publish_sms_marksheet' => 'deferred',
                'marksheets_admit_cards' => 'pending',
                'marks_csv_import' => 'deferred',
                'online_exam' => 'pending',
                'certificates' => 'pending',
            ],
        ]);
    }
}
