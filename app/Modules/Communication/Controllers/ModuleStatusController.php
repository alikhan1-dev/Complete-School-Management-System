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
            'message' => 'Email and SMS config save done. Deferred: notice board, compose mail/SMS, templates, test_mail/test_sms, Chat, FrontCms.',
            'slices' => [
                'email_config' => 'done',
                'sms_config' => 'done',
                'notice_board' => 'pending',
                'compose_mailsms' => 'pending',
                'templates' => 'pending',
                'test_send' => 'pending',
            ],
        ]);
    }
}
