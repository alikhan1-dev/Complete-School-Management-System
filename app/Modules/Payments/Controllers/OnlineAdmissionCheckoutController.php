<?php

namespace App\Modules\Payments\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Payments\Services\OnlineAdmissionCheckoutService;
use App\Modules\Payments\Services\PaymentSettingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;

/**
 * CI onlineadmission/Checkout + gateway index pages.
 * Live driver POST/checkout APIs are deferred.
 */
class OnlineAdmissionCheckoutController extends Controller
{
    public function __construct(
        protected OnlineAdmissionCheckoutService $checkout,
        protected PaymentSettingService $paymentSettings,
    ) {
    }

    public function index(Request $request): RedirectResponse|Response
    {
        $admissionId = (int) $request->input('admission_id');
        $referenceNo = (string) $request->input('reference_no', '');
        session()->put('reference', $admissionId);
        if ($referenceNo !== '') {
            session()->put('reference_no', $referenceNo);
        }

        $method = $this->paymentSettings->activeMethod();
        if ($method === null) {
            return response('', 200);
        }

        $path = $this->checkout->checkoutPathFor((string) $method->payment_type);
        if ($path === null) {
            return response('', 200);
        }

        return redirect($path);
    }

    public function successinvoice(string $referenceNo): View
    {
        return view('payments::onlineadmission.success_invoice', [
            'setting' => $this->checkout->school(),
            'reference_no' => $referenceNo,
        ]);
    }

    public function processinginvoice(?string $referenceNo = null): View
    {
        return view('payments::onlineadmission.processing_invoice', [
            'setting' => $this->checkout->school(),
            'reference_no' => (string) $referenceNo,
        ]);
    }

    public function paymentfailed(?string $referenceNo = null): View
    {
        return view('payments::onlineadmission.payment_failed', [
            'setting' => $this->checkout->school(),
            'reference_no' => (string) $referenceNo,
        ]);
    }

    public function gateway(string $gateway): View
    {
        $method = $this->paymentSettings->activeMethod();
        $amount = $this->checkout->amount();
        $fee = $this->checkout->processingFee($method, $amount);

        return view('payments::onlineadmission.gateway', [
            'setting' => $this->checkout->school(),
            'gateway' => $gateway,
            'amount' => $amount,
            'processingFee' => $fee,
            'total' => $amount + $fee,
        ]);
    }
}
