<?php

namespace App\Modules\Leave\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

/**
 * Phase 6 Leave migration status.
 */
class ModuleStatusController extends Controller
{
    public function status(): JsonResponse
    {
        return response()->json([
            'module' => 'Leave',
            'status' => 'done',
            'message' => 'Leave types, staff approve/self-apply, student approve_leave, and leave reports done. Deferred: class-teacher scope, SaaS quota, mail/SMS.',
            'slices' => [
                'leave_types' => 'done',
                'approve_leave_request' => 'done',
                'staff_self_apply' => 'done',
                'student_approve_leave' => 'done',
                'reports' => 'done',
            ],
        ]);
    }
}
