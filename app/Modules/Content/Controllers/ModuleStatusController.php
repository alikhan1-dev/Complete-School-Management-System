<?php

namespace App\Modules\Content\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

/**
 * Phase 8 Content migration status.
 */
class ModuleStatusController extends Controller
{
    public function status(): JsonResponse
    {
        return response()->json([
            'module' => 'Content',
            'status' => 'in_progress',
            'message' => 'Content type master + upload + share + user portal persist done. Deferred: live YouTube oEmbed, SaaS quota, CI pixel-parity JS, legacy contents category pages.',
            'slices' => [
                'content_type' => 'done',
                'upload' => 'done',
                'share' => 'done',
                'user_portal' => 'done',
            ],
        ]);
    }
}
