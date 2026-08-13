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
            'message' => 'Exam groups + exams + exam subjects CRUD done. Deferred: assign students, marks entry, link exams, marksheets/admit cards, OnlineExam, Certificates.',
            'slices' => [
                'exam_groups' => 'done',
                'exams_in_group' => 'done',
                'exam_subjects' => 'done',
                'assign_students' => 'pending',
                'marks_entry' => 'pending',
                'link_exams' => 'pending',
                'marksheets_admit_cards' => 'pending',
                'online_exam' => 'pending',
                'certificates' => 'pending',
            ],
        ]);
    }
}
