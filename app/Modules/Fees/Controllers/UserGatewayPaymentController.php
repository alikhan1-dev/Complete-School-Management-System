<?php

namespace App\Modules\Fees\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Fees\Services\PortalOnlinePayService;
use App\Modules\Payments\Services\StudentFeeGatewayPersistService;
use App\Modules\Shared\Services\SchoolContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use InvalidArgumentException;
use RuntimeException;

/**
 * CI user/gateway/Payment::pay online_payment + gateway checkout persist page.
 * Live driver charge APIs deferred to Payments module.
 */
class UserGatewayPaymentController extends Controller
{
    public function __construct(
        protected PortalOnlinePayService $onlinePay,
        protected StudentFeeGatewayPersistService $gatewayPersist,
        protected SchoolContext $school,
    ) {
    }

    /**
     * CI user/gateway/payment/pay — online_payment branch (offline handled elsewhere).
     */
    public function pay(Request $request): RedirectResponse
    {
        $mode = (string) $request->input('submit_mode', 'online_payment');
        abort_unless($mode === 'online_payment', 422);

        abort_unless($this->onlinePay->hasActivePaymentMethod(), 403);

        $sessionId = (int) (session('current_class.student_session_id') ?? 0);
        abort_if($sessionId <= 0, 403);

        try {
            $params = $this->onlinePay->startOnlinePayment($request->all(), $sessionId);
        } catch (InvalidArgumentException|RuntimeException $e) {
            return redirect()
                ->route('user.fees.getfees')
                ->withErrors(['payment' => $e->getMessage()]);
        }

        $gateway = strtolower((string) ($params['payment_type'] ?? 'gateway'));

        return redirect()->route('user.gateway.show', ['gateway' => $gateway]);
    }

    /**
     * Persist checkout page (CI redirects into user/gateway/{method}).
     */
    public function show(string $gateway): View|RedirectResponse
    {
        abort_unless($this->onlinePay->hasActivePaymentMethod(), 403);

        $params = $this->onlinePay->sessionParams();
        if ($params === null) {
            return redirect()
                ->route('user.fees.getfees')
                ->withErrors(['payment' => 'Select a fee line before online payment.']);
        }

        $params = $this->gatewayPersist->persistFromSession($params, $gateway);

        return view('shared::layouts.student_parent', [
            'title' => (string) __('system.payment_details'),
            'contentView' => 'fees::user.onlinepay.gateway',
            'gateway' => $gateway,
            'params' => $params,
            'currencySymbol' => $this->school->currencySymbol(),
        ]);
    }
}
