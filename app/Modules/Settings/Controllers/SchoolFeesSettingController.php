<?php

namespace App\Modules\Settings\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Roles\Services\PermissionService;
use App\Modules\Settings\Services\SchoolFeesSettingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\View\View;

/**
 * CI Schsettings::fees + savefees.
 */
class SchoolFeesSettingController extends Controller
{
    public function __construct(
        protected PermissionService $permissions,
        protected SchoolFeesSettingService $fees,
    ) {
    }

    public function index(): View
    {
        abort_unless($this->permissions->hasPrivilege('general_setting', 'can_view'), 403);

        $setting = $this->fees->current();
        abort_unless($setting !== null, 404);

        return view('shared::layouts.admin', [
            'title' => __('system.fees'),
            'contentView' => 'settings::admin.fees.index',
            'pageTitle' => __('system.fees'),
            'result' => $setting,
            'duplicateFeesInvoice' => $this->fees->duplicateInvoiceFlags($setting),
            'studentPartialEnabled' => $this->fees->isPartialPaymentEnabled($setting),
            'canEdit' => $this->permissions->hasPrivilege('general_setting', 'can_edit'),
        ]);
    }

    /**
     * CI Schsettings::savefees — JSON because CI JS posts this URL.
     */
    public function save(Request $request): JsonResponse|RedirectResponse
    {
        abort_unless($this->permissions->hasPrivilege('general_setting', 'can_edit'), 403);

        $validator = Validator::make($request->all(), [
            'is_duplicate_fees_invoice' => ['required', 'array', 'min:1'],
            'lock_grace_period' => ['required'],
            'fee_due_days' => ['required'],
        ], [], [
            'is_duplicate_fees_invoice' => __('system.print_fees_receipt_for'),
            'lock_grace_period' => __('system.fees_payment_grace_period'),
            'fee_due_days' => __('system.carry_forward_fees_due_days'),
        ]);

        if ($validator->fails()) {
            $error = [
                'is_duplicate_fees_invoice' => $validator->errors()->has('is_duplicate_fees_invoice')
                    ? '<p>'.$validator->errors()->first('is_duplicate_fees_invoice').'</p>'
                    : '',
                'fee_due_days' => $validator->errors()->has('fee_due_days')
                    ? '<p>'.$validator->errors()->first('fee_due_days').'</p>'
                    : '',
                'lock_grace_period' => $validator->errors()->has('lock_grace_period')
                    ? '<p>'.$validator->errors()->first('lock_grace_period').'</p>'
                    : '',
            ];

            if ($this->wantsCiJson($request)) {
                return response()->json(['status' => 'fail', 'error' => $error]);
            }

            return redirect('schsettings/fees')->withErrors($validator)->withInput();
        }

        $partialRaw = $request->input('student_partial_payment');
        $partialEnabled = in_array(strtolower(trim((string) $partialRaw)), ['enabled', '1', 'true', 'yes'], true);

        $this->fees->save([
            'id' => $request->input('sch_id'),
            'is_duplicate_fees_invoice' => implode(',', $request->input('is_duplicate_fees_invoice', [])),
            'single_page_print' => $request->has('single_page_print') ? 1 : 0,
            'fee_due_days' => $request->input('fee_due_days'),
            'lock_grace_period' => $request->input('lock_grace_period'),
            'collect_back_date_fees' => $request->input('collect_back_date_fees'),
            'display_previous_fees' => $request->input('display_previous_fees'),
            'is_student_feature_lock' => $request->input('is_student_feature_lock'),
            'is_offline_fee_payment' => $request->input('is_offline_fee_payment'),
            'offline_bank_payment_instruction' => $request->input('offline_bank_payment_instruction'),
            'fees_discount' => $request->input('fees_discount'),
            'student_partial_payment' => $partialEnabled ? '1' : '0',
        ]);

        if ($this->wantsCiJson($request)) {
            return response()->json([
                'status' => 'success',
                'error' => '',
                'message' => __('system.success_message'),
            ]);
        }

        return redirect('schsettings/fees')->with('success', __('system.success_message'));
    }

    protected function wantsCiJson(Request $request): bool
    {
        return $request->ajax()
            || $request->expectsJson()
            || $request->wantsJson()
            || str_contains((string) $request->header('Accept', ''), 'application/json');
    }
}
