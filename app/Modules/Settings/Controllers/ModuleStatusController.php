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
            'message' => 'Captcha + general setting + logo + login page background + backend theme + mobile app URL/colors + student/guardian panel + fees flags + ID auto-generation + attendance type (core) + maintenance + WhatsApp + chat delete flags + Google Drive picker setting persist done. Deferred: Envato andapp register, staff/student auto-attendance schedules + class times, miscellaneous, modules, currency, SaaS quota.',
            'slices' => [
                'captcha' => 'done',
                'general_setting' => 'done',
                'logos' => 'done',
                'login_page_background' => 'done',
                'backend_theme' => 'done',
                'mobile_app' => 'done',
                'student_guardian_panel' => 'done',
                'fees' => 'done',
                'id_auto_generation' => 'done',
                'attendance_type' => 'done',
                'maintenance' => 'done',
                'whatsapp' => 'done',
                'chat_setting' => 'done',
                'google_drive' => 'done',
                'attendance_schedules' => 'deferred',
                'modules' => 'deferred',
                'currency' => 'deferred',
            ],
        ]);
    }
}