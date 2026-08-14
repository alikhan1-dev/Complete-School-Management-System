<?php

namespace App\Modules\Communication\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Communication\Models\EmailTemplate;
use App\Modules\Communication\Models\SmsTemplate;
use App\Modules\Communication\Services\MailSmsTemplateService;
use App\Modules\Roles\Services\PermissionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * CI admin/mailsms email_template + sms_template CRUD.
 * Live send and SaaS storage quota are deferred.
 */
class MailSmsTemplateController extends Controller
{
    public function __construct(
        protected PermissionService $permissions,
        protected MailSmsTemplateService $templates,
    ) {
    }

    public function emailIndex(): View
    {
        abort_unless($this->permissions->hasPrivilege('email_template', 'can_view'), 403);

        return view('shared::layouts.admin', [
            'title' => 'Email Template List',
            'contentView' => 'communication::admin.email_template_list',
            'pageTitle' => 'Email Template List',
            'email_template_list' => $this->templates->listEmailTemplates(),
            'canAdd' => $this->permissions->hasPrivilege('email_template', 'can_add'),
            'canEdit' => $this->permissions->hasPrivilege('email_template', 'can_edit'),
            'canDelete' => $this->permissions->hasPrivilege('email_template', 'can_delete'),
        ]);
    }

    public function emailCreate(): View
    {
        abort_unless($this->permissions->hasPrivilege('email_template', 'can_add'), 403);

        return $this->emailForm(null);
    }

    public function addEmailTemplate(Request $request): RedirectResponse|JsonResponse
    {
        abort_unless($this->permissions->hasPrivilege('email_template', 'can_add'), 403);

        $request->validate([
            'title' => ['required', 'string', 'max:100'],
            'message' => ['required', 'string'],
        ]);

        $this->templates->addEmailTemplate($request->all(), $this->collectFiles($request));

        return $this->saved($request, 'communication.mailsms.email_template');
    }

    public function emailEdit(int $id): View
    {
        abort_unless($this->permissions->hasPrivilege('email_template', 'can_edit'), 403);
        $row = $this->templates->findEmailTemplate($id);
        abort_if($row === null, 404);

        return $this->emailForm($row);
    }

    /**
     * CI POST admin/mailsms/edit_email_template — JSON for the modal.
     */
    public function editEmailTemplateJson(Request $request): JsonResponse
    {
        abort_unless($this->permissions->hasPrivilege('email_template', 'can_edit'), 403);
        $id = (int) $request->input('id');
        $payload = $this->templates->emailTemplateData($id);
        abort_if($payload['data'] === null, 404);

        return response()->json($payload);
    }

    public function updateEmailTemplate(Request $request): RedirectResponse|JsonResponse
    {
        abort_unless($this->permissions->hasPrivilege('email_template', 'can_edit'), 403);

        $request->validate([
            'id' => ['required', 'integer'],
            'title' => ['required', 'string', 'max:100'],
            'message' => ['required', 'string'],
        ]);

        $row = $this->templates->findEmailTemplate((int) $request->input('id'));
        abort_if($row === null, 404);

        $this->templates->updateEmailTemplate(
            $row,
            $request->all(),
            (array) $request->input('template_attachment', []),
            $this->collectFiles($request),
        );

        return $this->saved($request, 'communication.mailsms.email_template');
    }

    public function deleteEmailTemplate(int $id): RedirectResponse
    {
        abort_unless($this->permissions->hasPrivilege('email_template', 'can_delete'), 403);
        $this->templates->deleteEmailTemplate($id);

        return redirect()
            ->route('communication.mailsms.email_template')
            ->with('success', 'Record deleted successfully.');
    }

    public function viewDocuments(Request $request): JsonResponse
    {
        abort_unless($this->permissions->hasPrivilege('email_template', 'can_view'), 403);
        $id = (int) $request->input('template_id');

        return response()->json([
            'status' => 1,
            'page' => $this->templates->viewDocumentsHtml($id),
        ]);
    }

    public function download(string $doc, ?string $name = null): BinaryFileResponse
    {
        abort_unless($this->permissions->hasPrivilege('email_template', 'can_view'), 403);
        $path = $this->templates->attachmentPath($doc);
        abort_if($path === null, 404);
        $downloadName = $name !== null && $name !== '' ? basename($name) : basename($doc);

        return response()->download($path, $downloadName);
    }

    public function templateData(Request $request): JsonResponse
    {
        abort_unless(
            $this->permissions->hasPrivilege('email', 'can_view')
            || $this->permissions->hasPrivilege('email_template', 'can_view'),
            403
        );

        return response()->json($this->templates->emailTemplateData((int) $request->input('template_id')));
    }

    public function smsIndex(): View
    {
        abort_unless($this->permissions->hasPrivilege('sms_template', 'can_view'), 403);

        return view('shared::layouts.admin', [
            'title' => 'SMS Template List',
            'contentView' => 'communication::admin.sms_template_list',
            'pageTitle' => 'SMS Template List',
            'sms_template_list' => $this->templates->listSmsTemplates(),
            'canAdd' => $this->permissions->hasPrivilege('sms_template', 'can_add'),
            'canEdit' => $this->permissions->hasPrivilege('sms_template', 'can_edit'),
            'canDelete' => $this->permissions->hasPrivilege('sms_template', 'can_delete'),
        ]);
    }

    public function smsCreate(): View
    {
        abort_unless($this->permissions->hasPrivilege('sms_template', 'can_add'), 403);

        return $this->smsForm(null);
    }

    public function addSmsTemplate(Request $request): RedirectResponse|JsonResponse
    {
        abort_unless($this->permissions->hasPrivilege('sms_template', 'can_add'), 403);

        $request->validate([
            'title' => ['required', 'string', 'max:100'],
            'message' => ['required', 'string'],
        ]);

        $this->templates->addSmsTemplate($request->all());

        return $this->saved($request, 'communication.mailsms.sms_template');
    }

    public function smsEdit(int $id): View
    {
        abort_unless($this->permissions->hasPrivilege('sms_template', 'can_edit'), 403);
        $row = $this->templates->findSmsTemplate($id);
        abort_if($row === null, 404);

        return $this->smsForm($row);
    }

    /**
     * CI POST admin/mailsms/edit_sms_template — HTML fragment in JSON.
     */
    public function editSmsTemplateJson(Request $request): JsonResponse
    {
        abort_unless($this->permissions->hasPrivilege('sms_template', 'can_edit'), 403);
        $id = (int) $request->input('id');
        $row = $this->templates->findSmsTemplate($id);
        abort_if($row === null, 404);
        $page = view('communication::admin.sms_template_edit_fragment', [
            'sms_template_list' => $row,
        ])->render();

        return response()->json(['status' => 1, 'page' => $page]);
    }

    public function updateSmsTemplate(Request $request): RedirectResponse|JsonResponse
    {
        abort_unless($this->permissions->hasPrivilege('sms_template', 'can_edit'), 403);

        $request->validate([
            'id' => ['required', 'integer'],
            'title' => ['required', 'string', 'max:100'],
            'message' => ['required', 'string'],
        ]);

        $row = $this->templates->findSmsTemplate((int) $request->input('id'));
        abort_if($row === null, 404);
        $this->templates->updateSmsTemplate($row, $request->all());

        return $this->saved($request, 'communication.mailsms.sms_template');
    }

    public function deleteSmsTemplate(int $id): RedirectResponse
    {
        abort_unless($this->permissions->hasPrivilege('sms_template', 'can_delete'), 403);
        $this->templates->deleteSmsTemplate($id);

        return redirect()
            ->route('communication.mailsms.sms_template')
            ->with('success', 'Record deleted successfully.');
    }

    public function smsTemplateData(Request $request): JsonResponse
    {
        abort_unless(
            $this->permissions->hasPrivilege('sms', 'can_view')
            || $this->permissions->hasPrivilege('sms_template', 'can_view'),
            403
        );

        return response()->json($this->templates->smsTemplateData((int) $request->input('template_id')));
    }

    protected function emailForm(?EmailTemplate $row): View
    {
        return view('shared::layouts.admin', [
            'title' => $row ? 'Edit Email Template' : 'Add Email Template',
            'contentView' => 'communication::admin.email_template_form',
            'pageTitle' => $row ? 'Edit Email Template' : 'Add Email Template',
            'template' => $row,
            'attachments' => $row ? $this->templates->emailAttachments((int) $row->id) : [],
        ]);
    }

    protected function smsForm(?SmsTemplate $row): View
    {
        return view('shared::layouts.admin', [
            'title' => $row ? 'Edit SMS Template' : 'Add SMS Template',
            'contentView' => 'communication::admin.sms_template_form',
            'pageTitle' => $row ? 'Edit SMS Template' : 'Add SMS Template',
            'template' => $row,
        ]);
    }

    /**
     * @return list<UploadedFile>
     */
    protected function collectFiles(Request $request): array
    {
        $out = [];
        foreach (['files', 'attachment'] as $field) {
            $files = $request->file($field, []);
            if ($files instanceof UploadedFile) {
                $files = [$files];
            }
            foreach ((array) $files as $file) {
                if ($file instanceof UploadedFile) {
                    $out[] = $file;
                }
            }
        }

        return $out;
    }

    protected function saved(Request $request, string $route): RedirectResponse|JsonResponse
    {
        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'status' => '1',
                'error' => '',
                'message' => 'Record saved successfully.',
            ]);
        }

        return redirect()->route($route)->with('success', 'Record saved successfully.');
    }
}
