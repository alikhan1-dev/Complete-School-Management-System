<?php

namespace App\Modules\Settings\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Roles\Services\PermissionService;
use App\Modules\Settings\Services\SchoolCurrencySettingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

/**
 * CI admin/Currency — list, price/symbol/status, school default, staff switcher, amount format.
 */
class SchoolCurrencySettingController extends Controller
{
    public function __construct(
        protected PermissionService $permissions,
        protected SchoolCurrencySettingService $currencies,
    ) {
    }

    public function index(): View
    {
        abort_unless($this->permissions->hasPrivilege('currency', 'can_view'), 403);

        $setting = $this->currencies->schoolSetting();
        abort_unless($setting !== null, 404);

        return view('shared::layouts.admin', [
            'title' => __('system.currencies'),
            'contentView' => 'settings::admin.currency.index',
            'pageTitle' => __('system.currencies'),
            'setting' => $setting,
            'languagelist' => $this->currencies->listCurrencies(),
        ]);
    }

    /**
     * CI admin/currency/editprice — JSON {status:1, message}.
     */
    public function editprice(Request $request): JsonResponse|RedirectResponse
    {
        $this->currencies->add([
            'id' => $request->input('currency_id'),
            'base_price' => $request->input('base_price'),
        ]);

        return $this->updated($request);
    }

    /**
     * CI admin/currency/editsymbol — JSON {status:1, message}.
     */
    public function editsymbol(Request $request): JsonResponse|RedirectResponse
    {
        $this->currencies->add([
            'id' => $request->input('currency_id'),
            'symbol' => $request->input('symbol'),
        ]);

        return $this->updated($request);
    }

    /**
     * CI admin/currency/changestatus — JSON {status:1, message}.
     */
    public function changestatus(Request $request): JsonResponse|RedirectResponse
    {
        $this->currencies->add([
            'id' => $request->input('id'),
            'is_active' => $request->input('status'),
        ]);

        return $this->updated($request);
    }

    /**
     * CI admin/currency/changeactive — writes sch_settings.currency.
     */
    public function changeactive(Request $request): JsonResponse|RedirectResponse
    {
        $this->currencies->updateSchoolCurrency([
            'id' => $request->input('id'),
            'currency' => $request->input('currency_id'),
        ]);

        return $this->updated($request);
    }

    /**
     * CI admin/currency/change_currency — staff display currency + admin session fields.
     */
    public function changeCurrency(Request $request): JsonResponse|RedirectResponse
    {
        $currencyId = (int) $request->input('currency_id');
        $currency = $this->currencies->find($currencyId);
        abort_unless($currency !== null, 404);

        $staff = Auth::guard('staff')->user();
        abort_unless($staff !== null, 403);

        $this->currencies->updateStaffCurrency((int) $staff->id, $currencyId);

        $admin = session('admin', []);
        $admin['currency_base_price'] = $currency->base_price;
        $admin['currency_symbol'] = $currency->symbol;
        $admin['currency'] = $currencyId;
        session(['admin' => $admin]);

        $message = __('system.currency_changed_successfully');

        if ($this->wantsCiJson($request)) {
            return response()->json(['status' => 1, 'message' => $message]);
        }

        return redirect('admin/currency')->with('success', $message);
    }

    /**
     * CI admin/currency/getAmountFormat — JSON {status:1, amount}.
     */
    public function getAmountFormat(Request $request): JsonResponse|RedirectResponse
    {
        $amount = $this->currencies->formatPostedAmount($request->input('total_fees_alloted'));

        if ($this->wantsCiJson($request)) {
            return response()->json(['status' => 1, 'amount' => $amount]);
        }

        return redirect('admin/currency');
    }

    protected function updated(Request $request): JsonResponse|RedirectResponse
    {
        $message = __('system.update_message');

        if ($this->wantsCiJson($request)) {
            return response()->json(['status' => 1, 'message' => $message]);
        }

        return redirect('admin/currency')->with('success', $message);
    }

    protected function wantsCiJson(Request $request): bool
    {
        return $request->ajax()
            || $request->expectsJson()
            || $request->wantsJson()
            || str_contains((string) $request->header('Accept', ''), 'application/json');
    }
}
