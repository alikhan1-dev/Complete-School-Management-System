<?php

namespace App\Modules\Payments\Services;

use App\Modules\OnlineAdmission\Models\OnlineAdmission;
use App\Modules\Payments\Models\OnlineAdmissionPayment;
use App\Modules\Payments\Models\PaymentSetting;
use App\Modules\Settings\Models\SchSetting;

/**
 * CI onlineadmission/Checkout + Onlinestudent_model::paymentSuccess persist.
 * Live gateway API calls and mail/SMS after pay are deferred.
 */
class OnlineAdmissionCheckoutService
{
    /**
     * CI Checkout::index redirect map (including onepay → icicipay).
     *
     * @return array<string, string>
     */
    public function checkoutPaths(): array
    {
        return [
            'payu' => 'onlineadmission/payu',
            'stripe' => 'onlineadmission/stripe',
            'ccavenue' => 'onlineadmission/ccavenue',
            'paypal' => 'onlineadmission/paypal',
            'instamojo' => 'onlineadmission/instamojo',
            'paytm' => 'onlineadmission/paytm',
            'razorpay' => 'onlineadmission/razorpay',
            'paystack' => 'onlineadmission/paystack',
            'midtrans' => 'onlineadmission/midtrans',
            'ipayafrica' => 'onlineadmission/ipayafrica',
            'jazzcash' => 'onlineadmission/jazzcash',
            'pesapal' => 'onlineadmission/pesapal',
            'flutterwave' => 'onlineadmission/flutterwave',
            'billplz' => 'onlineadmission/billplz',
            'sslcommerz' => 'onlineadmission/sslcommerz',
            'walkingm' => 'onlineadmission/walkingm',
            'mollie' => 'onlineadmission/mollie',
            'cashfree' => 'onlineadmission/cashfree',
            'payfast' => 'onlineadmission/payfast',
            'toyyibpay' => 'onlineadmission/toyyibpay',
            'twocheckout' => 'onlineadmission/twocheckout',
            'skrill' => 'onlineadmission/skrill',
            'payhere' => 'onlineadmission/payhere',
            'onepay' => 'onlineadmission/icicipay',
            'icicipay' => 'onlineadmission/icici',
            'kowri' => 'onlineadmission/kowri',
            'dpopay' => 'onlineadmission/dpopay',
            'momopay' => 'onlineadmission/momopay',
        ];
    }

    public function checkoutPathFor(string $paymentType): ?string
    {
        return $this->checkoutPaths()[$paymentType] ?? null;
    }

    public function school(): object
    {
        $row = SchSetting::query()->orderBy('id')->first();
        abort_if($row === null, 404);

        return $row;
    }

    public function paymentRequired(): bool
    {
        return (string) $this->school()->online_admission_payment === 'yes';
    }

    public function amount(): float
    {
        return (float) ($this->school()->online_admission_amount ?? 0);
    }

    public function processingFee(?PaymentSetting $method, float $amount): float
    {
        if ($method === null) {
            return 0.0;
        }
        if ($method->charge_type === 'percentage') {
            return ($amount * (float) $method->charge_value) / 100;
        }
        if ($method->charge_type === 'fix') {
            return (float) $method->charge_value;
        }

        return 0.0;
    }

    /**
     * CI Onlinestudent_model::paymentSuccess (mail/SMS deferred).
     *
     * @param  array<string, mixed>  $payment
     */
    public function paymentSuccess(array $payment): void
    {
        $paidStatus = 1;
        if (isset($payment['paid_status']) && $payment['paid_status'] !== '' && $payment['paid_status'] !== null) {
            $paidStatus = (int) $payment['paid_status'];
        }
        unset($payment['paid_status']);

        OnlineAdmission::query()->where('id', (int) $payment['online_admission_id'])->update([
            'paid_status' => $paidStatus,
            'form_status' => 1,
        ]);

        OnlineAdmissionPayment::query()->create($payment);
    }
}
