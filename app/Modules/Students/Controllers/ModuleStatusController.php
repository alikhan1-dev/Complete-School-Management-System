<?php

namespace App\Modules\Students\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class ModuleStatusController extends Controller
{
    public function status(): JsonResponse
    {
        return response()->json([
            'module' => 'Students',
            'status' => 'in_progress',
            'message' => 'Admission/profile/docs/timeline/disable + disable reason + disabled students list + alumni (list/details/events + class-teacher scope + table custom fields + details view + calendar getevent) + fees-on-admit + multi-class done. Deferred: alumni mail/SMS fan-out (Communication).',
            'slices' => [
                'admission_core' => 'done',
                'fees_on_admit' => 'done',
                'multi_class' => 'done',
                'multi_class_class_teacher_scope' => 'done',
                'documents' => 'done',
                'timeline' => 'done',
                'disable_reason' => 'done',
                'disabled_list' => 'done',
                'disabled_list_class_teacher_scope' => 'done',
                'disabled_list_custom_fields' => 'done',
                'alumni' => 'done',
                'alumni_class_teacher_scope' => 'done',
                'alumni_custom_fields' => 'done',
                'alumni_calendar_feed' => 'done',
                'promotion' => 'done',
            ],
        ]);
    }
}
