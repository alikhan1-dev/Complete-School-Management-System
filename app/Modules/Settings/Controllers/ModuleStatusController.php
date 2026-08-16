<?php

namespace App\Modules\Settings\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

/**
 * Placeholder for Phase 2–8 migration of the Settings module.
 * Feature code will be ported from smart_7.2 with parity testing.
 */
class ModuleStatusController extends Controller
{
    public function status(): JsonResponse
    {
        return response()->json([
            'module' => 'Settings',
            'status' => 'in_progress',
            'message' => 'Captcha setting persist + general school setting persist done. Deferred: logos, miscellaneous, modules, theme, currency, mobile app, fees flags, ID auto, attendance type, maintenance, WhatsApp, chat, Drive, SaaS quota.',
            'slices' => [
                'captcha' => 'done',
                'general_setting' => 'done',
                'logos' => 'deferred',
                'modules' => 'deferred',
                'theme' => 'deferred',
                'currency' => 'deferred',
            ],
        ]);
    }
}