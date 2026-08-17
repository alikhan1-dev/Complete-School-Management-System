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
            'message' => 'Content type master + upload + share persist done. User portal pending.',
            'slices' => [
                'content_type' => 'done',
                'upload' => 'done',
                'share' => 'done',
                'user_portal' => 'pending',
            ],
        ]);
    }
}
