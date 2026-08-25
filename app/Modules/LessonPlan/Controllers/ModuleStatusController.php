<?php

namespace App\Modules\LessonPlan\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

/**
 * Phase 6 LessonPlan migration status.
 */
class ModuleStatusController extends Controller
{
    public function status(): JsonResponse
    {
        return response()->json([
            'module' => 'LessonPlan',
            'status' => 'in_progress',
            'message' => 'LessonPlan admin core done (lesson, topic, status, copy, weekly syllabus, forum) + full admin class-teacher scope (lesson/topic/status/copy + weekly matrix). Deferred: student portal comments, DataTables AJAX, SaaS quota.',
            'slices' => [
                'lesson' => 'done',
                'topic' => 'done',
                'syllabus_status' => 'done',
                'copy_lesson' => 'done',
                'manage_lesson_plan_weekly' => 'done',
                'forum' => 'done',
                'class_teacher_lesson_topic' => 'done',
                'weekly_class_teacher_matrix' => 'done',
                'student_portal_comments' => 'deferred',
                'datatables_ajax' => 'deferred',
                'saas_quota' => 'deferred',
            ],
        ]);
    }
}
