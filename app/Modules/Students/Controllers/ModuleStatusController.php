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
            'message' => 'Admission/profile/docs/timeline/disable + disable reason + disabled students list (class-teacher scope + details view) + alumni list/details + alumni events + fees-on-admit + multi-class (admin search/save + admit/edit extras + delete guard + class-teacher matrix filter) done. Deferred: alumni mail/SMS + calendar JS; disabled list custom-field columns.',
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
                'alumni' => 'done',
                'promotion' => 'done',
            ],
        ]);
    }
}
