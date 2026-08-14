<?php

namespace App\Modules\Communication\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Communication\Services\SmsConfigService;
use App\Modules\Roles\Services\PermissionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * CI Smsconfig — per-gateway credentials. Enabling one disables the others.
 * Runtime send and test_sms are deferred with compose/Mailsms.
 */
class SmsConfigController extends Controller
{
    public function __construct(
        protected PermissionService $permissions,
        protected SmsConfigService $smsConfig,
    ) {
    }

    public function index(Request $request): View
    {
        abort_unless($this->permissions->hasPrivilege('sms_setting', 'can_view'), 403);

        $tabOrder = [
            'clickatell',
            'twilio',
            'msg_nineone',
            'text_local',
            'smscountry',
            'bulk_sms',
            'mobireach',
            'nexmo',
            'africastalking',
            'smseg',
            'smsgatewayhub',
            'custom',
        ];
        $gateways = $this->smsConfig->gateways();
        $ordered = [];
        foreach ($tabOrder as $type) {
            if (isset($gateways[$type])) {
                $ordered[$type] = $gateways[$type];
            }
        }

        return view('shared::layouts.admin', [
            'title' => 'SMS Config List',
            'contentView' => 'communication::admin.sms_list',
            'pageTitle' => 'SMS Setting',
            'statuslist' => $this->smsConfig->statusList(),
            'gateways' => $ordered,
            'smsByType' => $this->smsConfig->keyedByType(),
            'activeTab' => (string) $request->query('tab', 'clickatell'),
            'canEdit' => $this->permissions->hasPrivilege('sms_setting', 'can_edit'),
        ]);
    }

    public function save(Request $request, string $action): RedirectResponse|View
    {
        abort_unless($this->permissions->hasPrivilege('sms_setting', 'can_edit'), 403);

        $type = $this->smsConfig->typeForAction($action);
        abort_if($type === null, 404);

        $gateway = $this->smsConfig->gateways()[$type];
        $rules = [];
        foreach ($gateway['fields'] as $field) {
            if (! empty($field['required'])) {
                $rules[$field['name']] = ['required', 'string'];
            }
        }

        $request->validate($rules);
        $this->smsConfig->save($type, $request->all());

        return redirect()
            ->route('communication.smsconfig.index', ['tab' => $type])
            ->with('success', 'Record updated successfully.');
    }
}
