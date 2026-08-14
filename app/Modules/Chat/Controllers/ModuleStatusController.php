<?php

namespace App\Modules\Chat\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

/**
 * Phase 7 Chat migration status.
 */
class ModuleStatusController extends Controller
{
    public function status(): JsonResponse
    {
        return response()->json([
            'module' => 'Chat',
            'status' => 'in_progress',
            'message' => 'Staff admin and user/parent chat persist + polling done. Deferred: live mail/SMS/push, FrontCms.',
            'slices' => [
                'staff_chat' => 'done',
                'user_chat' => 'done',
            ],
        ]);
    }
}
