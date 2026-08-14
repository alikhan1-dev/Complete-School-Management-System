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
            'message' => 'LessonPlan admin core done (lesson, topic, status, copy, weekly syllabus, forum). Deferred: student portal comments, class-teacher scope, DataTables AJAX, SaaS quota.',
            'slices' => [
                'lesson' => 'done',
                'topic' => 'done',
                'syllabus_status' => 'done',
                'copy_lesson' => 'done',
                'manage_lesson_plan_weekly' => 'done',
                'forum' => 'done',
            ],
        ]);
    }
}
