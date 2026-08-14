<?php

namespace App\Modules\Payments\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Payments\Services\PaymentSettingService;
use App\Modules\Roles\Services\PermissionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * CI admin/Paymentsettings — persist gateway credentials and active method.
 * Live charges, webhooks, and onlineadmission/* drivers are deferred.
 */
class PaymentSettingController extends Controller
{
    public function __construct(
        protected PermissionService $permissions,
        protected PaymentSettingService $settings,
    ) {
    }

    public function index(): View
    {
        abort_unless($this->permissions->hasPrivilege('payment_methods', 'can_view'), 403);

        return view('shared::layouts.admin', [
            'title' => 'SMS Config List',
            'contentView' => 'payments::admin.paymentsetting_list',
            'pageTitle' => 'Payment Methods',
            'gateways' => $this->settings->gateways(),
            'rowsByType' => $this->settings->keyedByType(),
            'activeType' => $this->settings->activeType(),
            'canEdit' => $this->permissions->hasPrivilege('payment_methods', 'can_edit'),
        ]);
    }

    public function save(Request $request, string $action): RedirectResponse
    {
        abort_unless($this->permissions->hasPrivilege('payment_methods', 'can_edit'), 403);

        $type = $this->settings->typeForAction($action);
        abort_if($type === null, 404);

        $gateway = $this->settings->gateways()[$type];
        $rules = [];
        foreach ($gateway['fields'] as $field) {
            if (! empty($field['required'])) {
                $rules[$field['name']] = ['required', 'string'];
            }
        }

        $chargeType = (string) $request->input('charge_type', '');
        $chargeRequired = ! empty($gateway['charge_required_if_not_none'])
            ? $chargeType !== 'none'
            : ($chargeType !== 'none' && $chargeType !== '');
        if ($chargeRequired) {
            $rules[$gateway['charge_field']] = ['required', 'numeric'];
        }

        $request->validate($rules);
        $this->settings->save($type, $request->all());

        return redirect('admin/paymentsettings')->with('success', 'Record updated successfully.');
    }

    public function setting(Request $request): RedirectResponse
    {
        abort_unless($this->permissions->hasPrivilege('payment_methods', 'can_edit'), 403);

        $paymentSetting = (string) $request->input('payment_setting', '');
        $errors = [];
        if ($paymentSetting === '') {
            $errors['payment_setting'] = 'The Payment Setting field is required.';
        } elseif ($paymentSetting !== 'none' && ! $this->settings->exists($paymentSetting)) {
            $errors['payment_setting'] = 'Please fill your payment setting detail';
        }

        if ($errors !== []) {
            return redirect('admin/paymentsettings')->withErrors($errors)->withInput();
        }

        $this->settings->activate($paymentSetting);

        return redirect('admin/paymentsettings')->with('success', 'Record updated successfully.');
    }

    public function paymentGatewayConfig(Request $request): RedirectResponse
    {
        abort_unless($this->permissions->hasPrivilege('payment_methods', 'can_edit'), 403);

        $accountType = (string) $request->input('account_type', '');
        $paymentSetting = (string) $request->input('payment_setting', '');
        $errors = [];
        if ($paymentSetting === '') {
            $errors['payment_setting'] = 'The Payment Setting field is required.';
        } elseif ($paymentSetting !== 'none' && ! $this->settings->exists($paymentSetting)) {
            $errors['payment_setting'] = 'Please fill your payment setting detail';
        }
        if ($accountType === '') {
            $errors['account_type'] = 'The account_type --r field is required.';
        }
        if ($accountType !== 'none' && $accountType !== '' && trim((string) $request->input('fine_amount')) === '') {
            $errors['fine_amount'] = 'The Fine Amount field is required.';
        }

        if ($errors !== []) {
            return redirect('admin/paymentsettings')->withErrors($errors)->withInput();
        }

        $this->settings->saveGatewayConfig($request->all());

        return redirect('admin/paymentsettings')->with('success', 'Record updated successfully.');
    }
}
