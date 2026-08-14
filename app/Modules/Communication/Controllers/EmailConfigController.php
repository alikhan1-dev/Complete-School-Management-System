<?php

namespace App\Modules\Communication\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Communication\Services\EmailConfigService;
use App\Modules\Roles\Services\PermissionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * CI Emailconfig — mail engine settings (sendmail / SMTP / AWS SES).
 * Runtime send and test_mail are deferred with compose/Mailsms.
 */
class EmailConfigController extends Controller
{
    public function __construct(
        protected PermissionService $permissions,
        protected EmailConfigService $emailConfig,
    ) {
    }

    public function index(): View
    {
        abort_unless($this->permissions->hasPrivilege('email_setting', 'can_view'), 403);

        return $this->formPage();
    }

    public function save(Request $request): RedirectResponse|View
    {
        abort_unless($this->permissions->hasPrivilege('email_setting', 'can_edit'), 403);

        $rules = [
            'email_type' => ['required', 'in:sendmail,smtp,aws_ses'],
        ];
        if ($request->input('email_type') === 'smtp') {
            $rules['smtp_server'] = ['required', 'string'];
        }
        if ($request->input('email_type') === 'aws_ses') {
            $rules['aws_email'] = ['required', 'string'];
            $rules['access_key'] = ['required', 'string'];
            $rules['secret_access_key'] = ['required', 'string'];
            $rules['region'] = ['required', 'string'];
        }

        $validated = $request->validate($rules);

        $this->emailConfig->save(array_merge($request->all(), $validated));

        return redirect()
            ->route('communication.emailconfig.index')
            ->with('success', 'Record updated successfully.');
    }

    protected function formPage(): View
    {
        $row = $this->emailConfig->current();

        return view('shared::layouts.admin', [
            'title' => 'Email Config List',
            'contentView' => 'communication::admin.email_index',
            'pageTitle' => 'Email Setting',
            'mailMethods' => $this->emailConfig->mailMethods(),
            'smtpEncryption' => $this->emailConfig->smtpEncryption(),
            'smtpAuth' => $this->emailConfig->smtpAuth(),
            'emaillist' => $row,
            'canEdit' => $this->permissions->hasPrivilege('email_setting', 'can_edit'),
        ]);
    }
}
