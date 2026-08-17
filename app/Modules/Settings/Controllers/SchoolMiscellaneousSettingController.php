<?php

namespace App\Modules\Settings\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Roles\Services\PermissionService;
use App\Modules\Settings\Services\SchoolMiscellaneousSettingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * CI Schsettings::miscellaneous + savemiscellaneous.
 * No field validation; always success JSON.
 */
class SchoolMiscellaneousSettingController extends Controller
{
    public function __construct(
        protected PermissionService $permissions,
        protected SchoolMiscellaneousSettingService $miscellaneous,
    ) {
    }

    public function index(): View
    {
        abort_unless($this->permissions->hasPrivilege('general_setting', 'can_view'), 403);

        $setting = $this->miscellaneous->current();
        abort_unless($setting !== null, 404);

        return view('shared::layouts.admin', [
            'title' => __('system.miscellaneous'),
            'contentView' => 'settings::admin.miscellaneous.index',
            'pageTitle' => __('system.miscellaneous'),
            'result' => $setting,
            'canEdit' => $this->permissions->hasPrivilege('general_setting', 'can_edit'),
        ]);
    }

    /**
     * CI Schsettings::savemiscellaneous — JSON because CI JS posts this URL.
     */
    public function save(Request $request): JsonResponse|RedirectResponse
    {
        abort_unless($this->permissions->hasPrivilege('general_setting', 'can_edit'), 403);

        $eventReminderPosted = $request->input('event_reminder');
        $calendarEventReminder = $eventReminderPosted === 'enabled'
            ? $request->input('calendar_event_reminder')
            : '0';

        $this->miscellaneous->save([
            'id' => $request->input('sch_id'),
            'my_question' => $request->input('my_question'),
            'exam_result' => $request->input('exam_result'),
            'class_teacher' => $request->has('class_teacher') ? 'yes' : 'no',
            'superadmin_restriction' => $request->has('superadmin_restriction_mode') ? 'enabled' : 'disabled',
            'calendar_event_reminder' => $calendarEventReminder,
            'event_reminder' => $request->has('event_reminder') ? 'enabled' : 'disabled',
            'staff_notification_email' => $request->input('staff_notification_email'),
            'scan_code_type' => $request->input('scan_code_type'),
            'download_admit_card' => $request->input('download_admit_card'),
            'student_form_multi_class' => $request->has('student_form_multi_class') ? 'enabled' : 'disabled',
        ]);

        if ($this->wantsCiJson($request)) {
            return response()->json([
                'status' => 'success',
                'error' => '',
                'message' => __('system.success_message'),
            ]);
        }

        return redirect('schsettings/miscellaneous')->with('success', __('system.success_message'));
    }

    protected function wantsCiJson(Request $request): bool
    {
        return $request->ajax()
            || $request->expectsJson()
            || $request->wantsJson()
            || str_contains((string) $request->header('Accept', ''), 'application/json');
    }
}
