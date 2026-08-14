<?php

namespace App\Modules\Communication\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

/**
 * Phase 7 Communication migration status.
 */
class ModuleStatusController extends Controller
{
    public function status(): JsonResponse
    {
        return response()->json([
            'module' => 'Communication',
            'status' => 'in_progress',
            'message' => 'Email/SMS config, notice board, notification templates, compose persist, schedule editors, and email/SMS template CRUD done. Deferred: live send at schedule time, Chat, FrontCms.',
            'slices' => [
                'email_config' => 'done',
                'sms_config' => 'done',
                'notice_board' => 'done',
                'compose_mailsms' => 'done',
                'templates' => 'done',
                'email_sms_templates' => 'done',
                'test_send' => 'pending',
            ],
        ]);
    }
}
