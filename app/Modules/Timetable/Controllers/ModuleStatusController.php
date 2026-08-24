<?php

namespace App\Modules\Timetable\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

/**
 * Phase Timetable migration status.
 */
class ModuleStatusController extends Controller
{
    public function status(): JsonResponse
    {
        return response()->json([
            'module' => 'Timetable',
            'status' => 'in_progress',
            'message' => 'Class timetable create/save + class report + teacher mytimetable + print + duplicate-check + quick period generator done.',
            'slices' => [
                'class_create_save' => 'done',
                'class_report' => 'done',
                'teacher_timetable' => 'done',
                'print' => 'done',
                'duplicate_check' => 'done',
                'quick_period_generator' => 'done',
            ],
        ]);
    }
}
