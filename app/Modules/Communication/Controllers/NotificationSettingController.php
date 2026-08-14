<?php

namespace App\Modules\Communication\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Communication\Services\NotificationSettingService;
use App\Modules\Roles\Services\PermissionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * CI admin/notification/setting + template persist.
 * Does not send mail/SMS.
 */
class NotificationSettingController extends Controller
{
    public function __construct(
        protected PermissionService $permissions,
        protected NotificationSettingService $settings,
    ) {
    }

    public function index(): View
    {
        abort_unless($this->permissions->hasPrivilege('notification_setting', 'can_view'), 403);

        $whatsapp = $this->settings->whatsappModuleActive();
        $labels = [];
        $list = $this->settings->listAll();
        foreach ($list as $row) {
            $labels[$row->id] = $this->settings->eventLabel((string) $row->type);
        }

        return view('shared::layouts.admin', [
            'title' => 'Notification Setting',
            'contentView' => 'communication::admin.notification_setting',
            'pageTitle' => 'Notification Setting',
            'notificationlist' => $list,
            'eventLabels' => $labels,
            'whatsappActive' => $whatsapp,
            'canEdit' => $this->permissions->hasPrivilege('notification_setting', 'can_edit'),
        ]);
    }

    public function save(Request $request): RedirectResponse
    {
        abort_unless($this->permissions->hasPrivilege('notification_setting', 'can_edit'), 403);

        $request->validate([
            'ids' => ['required', 'array', 'min:1'],
        ]);

        $this->settings->saveFlags((array) $request->input('ids', []), $request->all());

        return redirect()
            ->route('communication.notification_setting.index')
            ->with('success', 'Record updated successfully.');
    }

    public function editTemplate(int $id): View
    {
        abort_unless($this->permissions->hasPrivilege('notification_setting', 'can_edit'), 403);

        $row = $this->settings->find($id);
        abort_if($row === null, 404);

        return view('shared::layouts.admin', [
            'title' => 'Template',
            'contentView' => 'communication::admin.notification_template_form',
            'record' => $row,
            'eventLabel' => $this->settings->eventLabel((string) $row->type),
            'whatsappActive' => $this->settings->whatsappModuleActive(),
        ]);
    }

    public function saveTemplate(Request $request): RedirectResponse
    {
        abort_unless($this->permissions->hasPrivilege('notification_setting', 'can_edit'), 403);

        $validated = $request->validate([
            'temp_id' => ['required', 'integer'],
            'template_subject' => ['required', 'string'],
            'template_message' => ['required', 'string'],
            'template_id' => ['nullable', 'string', 'max:100'],
            'whatsapp_template_id' => ['nullable', 'string', 'max:255'],
        ]);

        $row = $this->settings->find((int) $validated['temp_id']);
        abort_if($row === null, 404);

        $this->settings->saveTemplate($row, $request->all());

        return redirect()
            ->route('communication.notification_setting.index')
            ->with('success', 'Record updated successfully.');
    }

    public function viewTemplate(int $id): View
    {
        abort_unless($this->permissions->hasPrivilege('notification_setting', 'can_view'), 403);

        $row = $this->settings->find($id);
        abort_if($row === null, 404);
        $chrome = $this->settings->emailChrome();

        return view('shared::layouts.admin', [
            'title' => 'Template',
            'contentView' => 'communication::admin.notification_template_view',
            'subject' => (string) $row->subject,
            'body' => (string) $row->template,
            'emailHeader' => $chrome['header'],
            'emailFooter' => $chrome['footer'],
        ]);
    }
}
