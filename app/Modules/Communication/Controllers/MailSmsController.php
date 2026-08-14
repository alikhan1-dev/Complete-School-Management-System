<?php

namespace App\Modules\Communication\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Communication\Services\MailSmsService;
use App\Modules\Roles\Services\PermissionService;
use App\Modules\Shared\Services\SchoolContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\View\View;

/**
 * CI admin/mailsms — compose persist + schedule log and type-specific editors.
 * Live mailer/SMS/push send (including at schedule_date_time) is deferred.
 */
class MailSmsController extends Controller
{
    public function __construct(
        protected PermissionService $permissions,
        protected MailSmsService $mailSms,
        protected SchoolContext $school,
    ) {
    }

    public function index(): View
    {
        abort_unless($this->permissions->hasPrivilege('email_sms_log', 'can_view'), 403);

        return view('shared::layouts.admin', [
            'title' => 'Email / SMS Log',
            'contentView' => 'communication::admin.mailsms_log',
            'pageTitle' => 'Email SMS Log',
            'listMessage' => $this->mailSms->listLog(),
            'dateFormat' => $this->school->dateFormat() ?: 'd/m/Y',
            'canCompose' => $this->permissions->hasPrivilege('email', 'can_view'),
            'canComposeSms' => $this->permissions->hasPrivilege('sms', 'can_view'),
            'canDelete' => $this->permissions->hasPrivilege('email_sms_log', 'can_view'),
        ]);
    }

    public function schedule(): View
    {
        abort_unless($this->permissions->hasPrivilege('schedule_email_sms_log', 'can_view'), 403);

        return view('shared::layouts.admin', [
            'title' => 'Schedule Email SMS Log',
            'contentView' => 'communication::admin.mailsms_schedule',
            'pageTitle' => 'Schedule Email SMS Log',
            'listMessage' => $this->mailSms->listSchedule(),
            'dateFormat' => $this->school->dateFormat() ?: 'd/m/Y',
            'canEdit' => $this->permissions->hasPrivilege('schedule_email_sms_log', 'can_edit'),
            'canDelete' => $this->permissions->hasPrivilege('schedule_email_sms_log', 'can_delete'),
        ]);
    }

    public function editSchedule(int $id): View
    {
        abort_unless($this->permissions->hasPrivilege('schedule_email_sms_log', 'can_view'), 403);

        $row = $this->mailSms->findSchedule($id);
        abort_if($row === null, 404);

        $kind = $this->mailSms->scheduleKind($row);
        $classId = (int) ($row->schedule_class ?? 0);
        $view = match ($kind) {
            'email_group' => 'communication::admin.mailsms_schedule_edit_email_group',
            'email_individual' => 'communication::admin.mailsms_schedule_edit_email_individual',
            'email_class' => 'communication::admin.mailsms_schedule_edit_email_class',
            'sms_group' => 'communication::admin.mailsms_schedule_edit_sms_group',
            'sms_individual' => 'communication::admin.mailsms_schedule_edit_sms_individual',
            'sms_class' => 'communication::admin.mailsms_schedule_edit_sms_class',
            default => 'communication::admin.mailsms_schedule_edit',
        };

        return view('shared::layouts.admin', [
            'title' => 'Edit Schedule',
            'contentView' => $view,
            'pageTitle' => 'Edit Schedule',
            'message' => $row,
            'dateFormat' => $this->school->dateFormat() ?: 'd/m/Y',
            'canEdit' => $this->permissions->hasPrivilege('schedule_email_sms_log', 'can_edit'),
            'groupList' => $this->mailSms->decodeStringList($row->group_list),
            'sendThrough' => $this->mailSms->decodeStringList($row->send_through),
            'sendTo' => $this->mailSms->decodeStringList($row->send_to),
            'classId' => $classId,
            'selectedSections' => $this->mailSms->decodeStringList($row->schedule_section),
            'classSections' => $this->mailSms->sectionsForClass($classId),
            'userListJson' => $this->mailSms->individualUserListFormJson((string) $row->user_list),
            'emailTemplates' => $this->mailSms->emailTemplates(),
            'smsTemplates' => $this->mailSms->smsTemplates(),
            'sendThroughList' => $this->mailSms->sendThroughList(),
            'roles' => $this->mailSms->rolesForForm(),
            'showGuardian' => $this->mailSms->showGuardian(),
            'classList' => $this->mailSms->classList(),
        ]);
    }

    public function updateSchedule(Request $request, int $id): RedirectResponse
    {
        abort_unless($this->permissions->hasPrivilege('schedule_email_sms_log', 'can_edit'), 403);

        $row = $this->mailSms->findSchedule($id);
        abort_if($row === null, 404);

        $request->validate([
            'title' => ['required', 'string', 'max:200'],
            'message' => ['required', 'string'],
            'schedule_date_time' => ['required', 'string'],
        ]);

        $this->mailSms->updateSchedule($row, $request->all());

        return redirect()
            ->route('communication.mailsms.schedule')
            ->with('success', 'Record updated successfully.');
    }

    /**
     * CI POST admin/mailsms/update_group_schedule. Persist only — send_now does not send.
     */
    public function updateGroupSchedule(Request $request): RedirectResponse
    {
        abort_unless($this->permissions->hasPrivilege('schedule_email_sms_log', 'can_edit'), 403);

        $row = $this->scheduledRow((int) $request->input('message_id'));
        $request->validate([
            'group_title' => ['required', 'string', 'max:200'],
            'group_message' => ['required', 'string'],
            'user' => ['required', 'array', 'min:1'],
            'schedule_date_time' => ['required', 'string'],
        ]);

        $this->mailSms->updateGroupEmailSchedule(
            $row,
            $request->all(),
            array_map('strval', (array) $request->input('user', [])),
            $this->collectUploads($request, 'group_attachment'),
        );

        return $this->scheduleSaved();
    }

    /**
     * CI POST admin/mailsms/update_individual_schedule. Persist only.
     */
    public function updateIndividualSchedule(Request $request): RedirectResponse
    {
        abort_unless($this->permissions->hasPrivilege('schedule_email_sms_log', 'can_edit'), 403);

        $row = $this->scheduledRow((int) $request->input('message_id'));
        $request->validate([
            'individual_title' => ['required', 'string', 'max:200'],
            'individual_message' => ['required', 'string'],
            'user_list' => ['required', 'string'],
            'schedule_date_time' => ['required', 'string'],
        ]);

        $userArray = $this->mailSms->parseIndividualUserList((string) $request->input('user_list', ''));
        if ($userArray === []) {
            return redirect()->back()->withInput()->withErrors(['user_list' => 'The recipient field is required.']);
        }

        $this->mailSms->updateIndividualEmailSchedule(
            $row,
            $request->all(),
            $userArray,
            $this->collectUploads($request, 'induvidual_group_attachment'),
        );

        return $this->scheduleSaved();
    }

    /**
     * CI POST admin/mailsms/update_class_schedule. Persist only.
     */
    public function updateClassSchedule(Request $request): RedirectResponse
    {
        abort_unless($this->permissions->hasPrivilege('schedule_email_sms_log', 'can_edit'), 403);

        $row = $this->scheduledRow((int) $request->input('message_id'));
        $request->validate([
            'class_title' => ['required', 'string', 'max:200'],
            'class_message' => ['required', 'string'],
            'class_id' => ['required'],
            'user' => ['required', 'array', 'min:1'],
            'send_to' => ['required', 'array', 'min:1'],
            'send_to.*' => ['in:student,parent'],
            'schedule_date_time' => ['required', 'string'],
        ]);

        $this->mailSms->updateClassEmailSchedule(
            $row,
            $request->all(),
            array_map('strval', (array) $request->input('user', [])),
            array_map('strval', (array) $request->input('send_to', [])),
            $this->collectUploads($request, 'class_group_attachment'),
        );

        return $this->scheduleSaved();
    }

    /**
     * CI POST admin/mailsms/update_group_sms_schedule. Persist only.
     */
    public function updateGroupSmsSchedule(Request $request): RedirectResponse
    {
        abort_unless($this->permissions->hasPrivilege('schedule_email_sms_log', 'can_edit'), 403);

        $row = $this->scheduledRow((int) $request->input('message_id'));
        $request->validate([
            'group_title' => ['required', 'string', 'max:200'],
            'group_message' => ['required', 'string'],
            'user' => ['required', 'array', 'min:1'],
            'group_send_by' => ['required', 'array', 'min:1'],
            'group_send_by.*' => ['in:sms,push'],
            'schedule_date_time' => ['required', 'string'],
            'group_template_id' => ['nullable', 'string', 'max:100'],
            'template_id' => ['nullable'],
        ]);

        $this->mailSms->updateGroupSmsSchedule(
            $row,
            $request->all(),
            array_map('strval', (array) $request->input('user', [])),
            array_map('strval', (array) $request->input('group_send_by', [])),
        );

        return $this->scheduleSaved();
    }

    /**
     * CI POST admin/mailsms/update_individual_sms_schedule. Persist only.
     */
    public function updateIndividualSmsSchedule(Request $request): RedirectResponse
    {
        abort_unless($this->permissions->hasPrivilege('schedule_email_sms_log', 'can_edit'), 403);

        $row = $this->scheduledRow((int) $request->input('message_id'));
        $request->validate([
            'individual_title' => ['required', 'string', 'max:200'],
            'individual_message' => ['required', 'string'],
            'user_list' => ['required', 'string'],
            'individual_send_by' => ['required', 'array', 'min:1'],
            'individual_send_by.*' => ['in:sms,push'],
            'schedule_date_time' => ['required', 'string'],
            'individual_template_id' => ['nullable', 'string', 'max:100'],
            'template_id' => ['nullable'],
        ]);

        $userArray = $this->mailSms->parseIndividualUserList((string) $request->input('user_list', ''), true);
        if ($userArray === []) {
            return redirect()->back()->withInput()->withErrors(['user_list' => 'The recipient field is required.']);
        }

        $this->mailSms->updateIndividualSmsSchedule(
            $row,
            $request->all(),
            $userArray,
            array_map('strval', (array) $request->input('individual_send_by', [])),
        );

        return $this->scheduleSaved();
    }

    /**
     * CI POST admin/mailsms/update_class_sms_schedule. Persist only.
     */
    public function updateClassSmsSchedule(Request $request): RedirectResponse
    {
        abort_unless($this->permissions->hasPrivilege('schedule_email_sms_log', 'can_edit'), 403);

        $row = $this->scheduledRow((int) $request->input('message_id'));
        $request->validate([
            'class_title' => ['required', 'string', 'max:200'],
            'class_message' => ['required', 'string'],
            'class_id' => ['required'],
            'user' => ['required', 'array', 'min:1'],
            'send_to' => ['required', 'array', 'min:1'],
            'send_to.*' => ['in:student,parent'],
            'class_send_by' => ['required', 'array', 'min:1'],
            'class_send_by.*' => ['in:sms,push'],
            'schedule_date_time' => ['required', 'string'],
            'class_template_id' => ['nullable', 'string', 'max:100'],
            'template_id' => ['nullable'],
        ]);

        $this->mailSms->updateClassSmsSchedule(
            $row,
            $request->all(),
            array_map('strval', (array) $request->input('user', [])),
            array_map('strval', (array) $request->input('send_to', [])),
            array_map('strval', (array) $request->input('class_send_by', [])),
        );

        return $this->scheduleSaved();
    }

    public function deleteSchedule(Request $request): RedirectResponse|JsonResponse
    {
        abort_unless($this->permissions->hasPrivilege('schedule_email_sms_log', 'can_delete'), 403);

        $id = (int) $request->input('message_id');
        abort_if($id <= 0, 404);
        $this->mailSms->deleteSchedule($id);

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'status' => '1',
                'message' => 'Record deleted successfully',
            ]);
        }

        return redirect()
            ->route('communication.mailsms.schedule')
            ->with('success', 'Record deleted successfully.');
    }

    public function deleteLog(): RedirectResponse|JsonResponse
    {
        abort_unless($this->permissions->hasPrivilege('email_sms_log', 'can_view'), 403);

        $this->mailSms->deleteSentLog();

        if (request()->expectsJson() || request()->ajax()) {
            return response()->json([
                'status' => 'success',
                'error' => '',
                'message' => 'Record deleted successfully',
            ]);
        }

        return redirect()
            ->route('communication.mailsms.index')
            ->with('success', 'Record deleted successfully.');
    }

    public function compose(): View
    {
        abort_unless($this->permissions->hasPrivilege('email', 'can_view'), 403);

        return view('shared::layouts.admin', [
            'title' => 'Send Email',
            'contentView' => 'communication::admin.mailsms_compose',
            'pageTitle' => 'Send Email',
            'emailTemplates' => $this->mailSms->emailTemplates(),
            'roles' => $this->mailSms->rolesForForm(),
            'showGuardian' => $this->mailSms->showGuardian(),
            'classList' => $this->mailSms->classList(),
            'birthDaysList' => $this->mailSms->birthdayList(),
        ]);
    }

    public function sendGroup(Request $request): RedirectResponse
    {
        abort_unless($this->permissions->hasPrivilege('email', 'can_view'), 403);

        $rules = [
            'group_title' => ['required', 'string', 'max:200'],
            'group_message' => ['required', 'string'],
            'user' => ['required', 'array', 'min:1'],
            'send_type' => ['nullable', 'in:send_now,schedule'],
        ];
        if ($request->input('send_type') === 'schedule') {
            $rules['schedule_date_time'] = ['required', 'string'];
        }
        $request->validate($rules);

        $files = $request->file('group_attachment', []);
        if ($files instanceof UploadedFile) {
            $files = [$files];
        }
        $fromFiles = $request->file('files', []);
        if ($fromFiles instanceof UploadedFile) {
            $fromFiles = [$fromFiles];
        }
        $uploads = array_values(array_filter(array_merge((array) $files, (array) $fromFiles)));

        $this->mailSms->sendGroupEmail(
            $request->all(),
            array_map('strval', (array) $request->input('user', [])),
            $uploads,
        );

        return redirect()
            ->route('communication.mailsms.index')
            ->with('success', 'Message sent successfully.');
    }

    public function search(Request $request): JsonResponse
    {
        abort_unless(
            $this->permissions->hasPrivilege('email', 'can_view')
            || $this->permissions->hasPrivilege('sms', 'can_view'),
            403
        );

        return response()->json($this->mailSms->searchRecipients(
            (string) $request->input('keyword', ''),
            (string) $request->input('category', ''),
        ));
    }

    public function sendIndividual(Request $request): RedirectResponse
    {
        abort_unless($this->permissions->hasPrivilege('email', 'can_view'), 403);

        $rules = [
            'individual_title' => ['required', 'string', 'max:200'],
            'individual_message' => ['required', 'string'],
            'user_list' => ['required', 'string'],
            'individual_send_by' => ['required', 'string'],
            'individual_send_type' => ['nullable', 'in:send_now,schedule'],
        ];
        if ($request->input('individual_send_type') === 'schedule') {
            $rules['schedule_date_time'] = ['required', 'string'];
        }
        $request->validate($rules);

        $userArray = $this->mailSms->parseIndividualUserList((string) $request->input('user_list', ''));
        if ($userArray === []) {
            return redirect()
                ->back()
                ->withInput()
                ->withErrors(['user_list' => 'The recipient field is required.']);
        }

        $files = $request->file('induvidual_group_attachment', []);
        if ($files instanceof UploadedFile) {
            $files = [$files];
        }
        $fromFiles = $request->file('files', []);
        if ($fromFiles instanceof UploadedFile) {
            $fromFiles = [$fromFiles];
        }
        $uploads = array_values(array_filter(array_merge((array) $files, (array) $fromFiles)));

        $this->mailSms->sendIndividualEmail($request->all(), $userArray, $uploads);

        return redirect()
            ->route('communication.mailsms.index')
            ->with('success', 'Message sent successfully.');
    }

    public function sendClass(Request $request): RedirectResponse
    {
        abort_unless($this->permissions->hasPrivilege('email', 'can_view'), 403);

        $rules = [
            'class_title' => ['required', 'string', 'max:200'],
            'class_message' => ['required', 'string'],
            'class_id' => ['required'],
            'user' => ['required', 'array', 'min:1'],
            'send_to' => ['required', 'array', 'min:1'],
            'send_to.*' => ['in:student,parent'],
            'class_send_by' => ['required', 'string'],
            'class_send_type' => ['nullable', 'in:send_now,schedule'],
        ];
        if ($request->input('class_send_type') === 'schedule') {
            $rules['schedule_date_time'] = ['required', 'string'];
        }
        $request->validate($rules);

        $files = $request->file('class_group_attachment', []);
        if ($files instanceof UploadedFile) {
            $files = [$files];
        }
        $fromFiles = $request->file('files', []);
        if ($fromFiles instanceof UploadedFile) {
            $fromFiles = [$fromFiles];
        }
        $uploads = array_values(array_filter(array_merge((array) $files, (array) $fromFiles)));

        $this->mailSms->sendClassEmail(
            $request->all(),
            array_map('strval', (array) $request->input('user', [])),
            array_map('strval', (array) $request->input('send_to', [])),
            $uploads,
        );

        return redirect()
            ->route('communication.mailsms.index')
            ->with('success', 'Message sent successfully.');
    }

    public function composeSms(): View
    {
        abort_unless($this->permissions->hasPrivilege('sms', 'can_view'), 403);

        return view('shared::layouts.admin', [
            'title' => 'Send SMS',
            'contentView' => 'communication::admin.mailsms_compose_sms',
            'pageTitle' => 'Send SMS',
            'smsTemplates' => $this->mailSms->smsTemplates(),
            'sendThroughList' => $this->mailSms->sendThroughList(),
            'roles' => $this->mailSms->rolesForForm(),
            'showGuardian' => $this->mailSms->showGuardian(),
            'classList' => $this->mailSms->classList(),
            'birthDaysList' => $this->mailSms->birthdayList(),
        ]);
    }

    public function sendGroupSms(Request $request): RedirectResponse
    {
        abort_unless($this->permissions->hasPrivilege('sms', 'can_view'), 403);

        $rules = [
            'group_title' => ['required', 'string', 'max:200'],
            'group_message' => ['required', 'string'],
            'user' => ['required', 'array', 'min:1'],
            'group_send_by' => ['required', 'array', 'min:1'],
            'group_send_by.*' => ['in:sms,push'],
            'send_type' => ['nullable', 'in:send_now,schedule'],
            'group_template_id' => ['nullable', 'string', 'max:100'],
            'template_id' => ['nullable'],
        ];
        if ($request->input('send_type') === 'schedule') {
            $rules['schedule_date_time'] = ['required', 'string'];
        }
        $request->validate($rules);

        $this->mailSms->sendGroupSms(
            $request->all(),
            array_map('strval', (array) $request->input('user', [])),
            array_map('strval', (array) $request->input('group_send_by', [])),
        );

        return redirect()
            ->route('communication.mailsms.index')
            ->with('success', 'Message sent successfully.');
    }

    public function sendIndividualSms(Request $request): RedirectResponse
    {
        abort_unless($this->permissions->hasPrivilege('sms', 'can_view'), 403);

        $rules = [
            'individual_title' => ['required', 'string', 'max:200'],
            'individual_message' => ['required', 'string'],
            'user_list' => ['required', 'string'],
            'individual_send_by' => ['required', 'array', 'min:1'],
            'individual_send_by.*' => ['in:sms,push'],
            'individual_send_type' => ['nullable', 'in:send_now,schedule'],
            'individual_template_id' => ['nullable', 'string', 'max:100'],
            'template_id' => ['nullable'],
        ];
        if ($request->input('individual_send_type') === 'schedule') {
            $rules['schedule_date_time'] = ['required', 'string'];
        }
        $request->validate($rules);

        $userArray = $this->mailSms->parseIndividualUserList((string) $request->input('user_list', ''), true);
        if ($userArray === []) {
            return redirect()
                ->back()
                ->withInput()
                ->withErrors(['user_list' => 'The recipient field is required.']);
        }

        $this->mailSms->sendIndividualSms(
            $request->all(),
            $userArray,
            array_map('strval', (array) $request->input('individual_send_by', [])),
        );

        return redirect()
            ->route('communication.mailsms.index')
            ->with('success', 'Message sent successfully.');
    }

    public function sendClassSms(Request $request): RedirectResponse
    {
        abort_unless($this->permissions->hasPrivilege('sms', 'can_view'), 403);

        $rules = [
            'class_title' => ['required', 'string', 'max:200'],
            'class_message' => ['required', 'string'],
            'class_id' => ['required'],
            'user' => ['required', 'array', 'min:1'],
            'send_to' => ['required', 'array', 'min:1'],
            'send_to.*' => ['in:student,parent'],
            'class_send_by' => ['required', 'array', 'min:1'],
            'class_send_by.*' => ['in:sms,push'],
            'class_send_type' => ['nullable', 'in:send_now,schedule'],
            'class_template_id' => ['nullable', 'string', 'max:100'],
            'template_id' => ['nullable'],
        ];
        if ($request->input('class_send_type') === 'schedule') {
            $rules['schedule_date_time'] = ['required', 'string'];
        }
        $request->validate($rules);

        $this->mailSms->sendClassSms(
            $request->all(),
            array_map('strval', (array) $request->input('user', [])),
            array_map('strval', (array) $request->input('send_to', [])),
            array_map('strval', (array) $request->input('class_send_by', [])),
        );

        return redirect()
            ->route('communication.mailsms.index')
            ->with('success', 'Message sent successfully.');
    }

    public function sendBirthday(Request $request): RedirectResponse
    {
        abort_unless($this->permissions->hasPrivilege('email', 'can_view'), 403);

        $request->validate([
            'birthday_title' => ['required', 'string', 'max:200'],
            'birthday_message' => ['required', 'string'],
            'birthday_send_by' => ['required', 'string'],
            'user' => ['required', 'array', 'min:1'],
        ]);

        $files = $request->file('birthday_group_attachment', []);
        if ($files instanceof UploadedFile) {
            $files = [$files];
        }
        $fromFiles = $request->file('files', []);
        if ($fromFiles instanceof UploadedFile) {
            $fromFiles = [$fromFiles];
        }
        $uploads = array_values(array_filter(array_merge((array) $files, (array) $fromFiles)));

        $this->mailSms->sendBirthdayEmail(
            $request->all(),
            array_map('strval', (array) $request->input('user', [])),
            $uploads,
        );

        return redirect()
            ->route('communication.mailsms.index')
            ->with('success', 'Message sent successfully.');
    }

    public function sendBirthdaySms(Request $request): RedirectResponse
    {
        abort_unless($this->permissions->hasPrivilege('sms', 'can_view'), 403);

        $request->validate([
            'birthday_title' => ['required', 'string', 'max:200'],
            'birthday_message' => ['required', 'string'],
            'birthday_send_by' => ['required', 'array', 'min:1'],
            'birthday_send_by.*' => ['in:sms,push'],
            'user' => ['required', 'array', 'min:1'],
            'birthday_template_id' => ['nullable', 'string', 'max:100'],
            'template_id' => ['nullable'],
        ]);

        $this->mailSms->sendBirthdaySms(
            $request->all(),
            array_map('strval', (array) $request->input('user', [])),
            array_map('strval', (array) $request->input('birthday_send_by', [])),
        );

        return redirect()
            ->route('communication.mailsms.index')
            ->with('success', 'Message sent successfully.');
    }

    /**
     * @return list<UploadedFile>
     */
    protected function collectUploads(Request $request, string $field): array
    {
        $files = $request->file($field, []);
        if ($files instanceof UploadedFile) {
            $files = [$files];
        }
        $fromFiles = $request->file('files', []);
        if ($fromFiles instanceof UploadedFile) {
            $fromFiles = [$fromFiles];
        }

        return array_values(array_filter(array_merge((array) $files, (array) $fromFiles)));
    }

    protected function scheduledRow(int $id): \App\Modules\Communication\Models\Message
    {
        abort_if($id <= 0, 404);
        $row = $this->mailSms->findSchedule($id);
        abort_if($row === null, 404);

        return $row;
    }

    protected function scheduleSaved(): RedirectResponse
    {
        return redirect()
            ->route('communication.mailsms.schedule')
            ->with('success', 'Record updated successfully.');
    }
}
