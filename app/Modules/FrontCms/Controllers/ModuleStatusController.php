<?php

namespace App\Modules\FrontCms\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

/**
 * Front CMS migration status.
 */
class ModuleStatusController extends Controller
{
    public function status(): JsonResponse
    {
        return response()->json([
            'module' => 'FrontCms',
            'status' => 'in_progress',
            'message' => 'Front CMS admin persist + public site done. Deferred: SaaS quota, live YouTube oEmbed, live contact/complain mail, admission/examresult on Welcome, CI theme pixel-parity.',
            'slices' => [
                'settings' => 'done',
                'pages' => 'done',
                'banners' => 'done',
                'gallery' => 'done',
                'menus' => 'done',
                'notices' => 'done',
                'events' => 'done',
                'media' => 'done',
                'public_site' => 'done',
            ],
        ]);
    }
}
