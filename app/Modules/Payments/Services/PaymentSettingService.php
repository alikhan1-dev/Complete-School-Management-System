<?php

namespace App\Modules\Payments\Services;

use App\Modules\Payments\Models\PaymentSetting;

/**
 * CI Paymentsetting_model — upsert credentials by payment_type; activate one, disable others.
 * Live gateway charges, webhooks, and onlineadmission/* drivers are deferred.
 */
class PaymentSettingService
{
    /**
     * @return array<string, array<string, mixed>>
     */
    public function gateways(): array
    {
        return [
            'paypal' => [
                'label' => 'Paypal',
                'action' => 'paypal',
                'charge_field' => 'paypal_charge_value',
                'fields' => [
                    ['name' => 'paypal_username', 'label' => 'Username', 'column' => 'api_username', 'required' => true],
                    ['name' => 'paypal_password', 'label' => 'Password', 'column' => 'api_password', 'input' => 'password', 'required' => true],
                    ['name' => 'paypal_signature', 'label' => 'Signature', 'column' => 'api_signature', 'required' => true],
                ],
                'extra' => ['paypal_demo' => 'TRUE'],
            ],
            'stripe' => [
                'label' => 'Stripe',
                'action' => 'stripe',
                'charge_field' => 'stripe_charge_value',
                'fields' => [
                    ['name' => 'api_secret_key', 'label' => 'Secret Key', 'column' => 'api_secret_key', 'required' => true],
                    ['name' => 'api_publishable_key', 'label' => 'Publishable Key', 'column' => 'api_publishable_key', 'required' => true],
                ],
            ],
            'payu' => [
                'label' => 'PayU',
                'action' => 'payu',
                'charge_field' => 'payu_charge_value',
                'fields' => [
                    ['name' => 'key', 'label' => 'Key', 'column' => 'api_secret_key', 'required' => true],
                    ['name' => 'salt', 'label' => 'Salt', 'column' => 'salt', 'required' => true],
                ],
            ],
            'ccavenue' => [
                'label' => 'CCAvenue',
                'action' => 'ccavenue',
                'charge_field' => 'ccavenue_charge_value',
                'charge_required_if_not_none' => true,
                'fields' => [
                    ['name' => 'ccavenue_secret', 'label' => 'Merchant ID', 'column' => 'api_secret_key', 'required' => true],
                    ['name' => 'ccavenue_salt', 'label' => 'Working Key', 'column' => 'salt', 'required' => true],
                    ['name' => 'ccavenue_api_publishable_key', 'label' => 'Access Code', 'column' => 'api_publishable_key', 'required' => true],
                ],
            ],
            'instamojo' => [
                'label' => 'Instamojo',
                'action' => 'instamojo',
                'charge_field' => 'instamojo_charge_value',
                'fields' => [
                    ['name' => 'instamojo_apikey', 'label' => 'Private API Key', 'column' => 'api_secret_key', 'required' => true],
                    ['name' => 'instamojo_authtoken', 'label' => 'Private Auth Token', 'column' => 'api_publishable_key', 'required' => true],
                    ['name' => 'instamojo_salt', 'label' => 'Private Salt', 'column' => 'salt', 'required' => true],
                ],
            ],
            'paystack' => [
                'label' => 'Paystack',
                'action' => 'paystack',
                'charge_field' => 'paystack_charge_value',
                'fields' => [
                    ['name' => 'paystack_secretkey', 'label' => 'Key', 'column' => 'api_secret_key', 'required' => true],
                ],
            ],
            'razorpay' => [
                'label' => 'Razorpay',
                'action' => 'razorpay',
                'charge_field' => 'razorpay_charge_value',
                'fields' => [
                    ['name' => 'razorpay_keyid', 'label' => 'Key', 'column' => 'api_publishable_key', 'required' => true],
                    ['name' => 'razorpay_secretkey', 'label' => 'Secret Key', 'column' => 'api_secret_key', 'required' => true],
                ],
            ],
            'paytm' => [
                'label' => 'Paytm',
                'action' => 'paytm',
                'charge_field' => 'paytm_charge_value',
                'fields' => [
                    ['name' => 'paytm_merchantid', 'label' => 'Merchant ID', 'column' => 'api_publishable_key', 'required' => true],
                    ['name' => 'paytm_merchantkey', 'label' => 'Merchant Key', 'column' => 'api_secret_key', 'required' => true],
                    ['name' => 'paytm_website', 'label' => 'Website', 'column' => 'paytm_website', 'required' => true],
                    ['name' => 'paytm_industrytype', 'label' => 'Industry Type', 'column' => 'paytm_industrytype', 'required' => true],
                ],
            ],
            'midtrans' => [
                'label' => 'Midtrans',
                'action' => 'midtrans',
                'charge_field' => 'midtrans_charge_value',
                'fields' => [
                    ['name' => 'midtrans_serverkey', 'label' => 'Key', 'column' => 'api_secret_key', 'required' => true],
                ],
            ],
            'pesapal' => [
                'label' => 'Pesapal',
                'action' => 'pesapal',
                'charge_field' => 'pesapal_charge_value',
                'fields' => [
                    ['name' => 'pesapal_consumer_key', 'label' => 'Consumer Key', 'column' => 'api_publishable_key', 'required' => true],
                    ['name' => 'pesapal_consumer_secret', 'label' => 'Consumer Secret', 'column' => 'api_secret_key', 'required' => true],
                ],
            ],
            'flutterwave' => [
                'label' => 'Flutter Wave',
                'action' => 'flutterwave',
                'charge_field' => 'flutterwave_charge_value',
                'fields' => [
                    ['name' => 'public_key', 'label' => 'Public Key', 'column' => 'api_publishable_key', 'required' => true],
                    ['name' => 'secret_key', 'label' => 'Secret Key', 'column' => 'api_secret_key', 'required' => true],
                ],
            ],
            'ipayafrica' => [
                'label' => 'iPay Africa',
                'action' => 'ipayafrica',
                'charge_field' => 'ipayafrica_charge_value',
                'fields' => [
                    ['name' => 'ipayafrica_vendorid', 'label' => 'Vendor ID', 'column' => 'api_publishable_key', 'required' => true],
                    ['name' => 'ipayafrica_hashkey', 'label' => 'Hash Key', 'column' => 'api_secret_key', 'required' => true],
                ],
            ],
            'jazzcash' => [
                'label' => 'JazzCash',
                'action' => 'jazzcash',
                'charge_field' => 'jazzcash_charge_value',
                'fields' => [
                    ['name' => 'jazzcash_pp_MerchantID', 'label' => 'Merchant ID', 'column' => 'api_secret_key', 'required' => true],
                    ['name' => 'jazzcash_pp_Password', 'label' => 'PP Password', 'column' => 'api_password', 'input' => 'password', 'required' => true],
                ],
            ],
            'billplz' => [
                'label' => 'Billplz',
                'action' => 'billplz',
                'charge_field' => 'billplz_charge_value',
                'fields' => [
                    ['name' => 'billplz_api_key', 'label' => 'API Key', 'column' => 'api_secret_key', 'required' => true],
                    ['name' => 'billplz_customer_service_email', 'label' => 'Customer Service Email', 'column' => 'api_email', 'required' => true],
                ],
            ],
            'sslcommerz' => [
                'label' => 'SSLCommerz',
                'action' => 'sslcommerz',
                'charge_field' => 'sslcommerz_charge_value',
                'fields' => [
                    ['name' => 'sslcommerz_api_key', 'label' => 'Store ID', 'column' => 'api_publishable_key', 'required' => true],
                    ['name' => 'sslcommerz_store_password', 'label' => 'Store Password', 'column' => 'api_password', 'input' => 'password', 'required' => true],
                ],
            ],
            'walkingm' => [
                'label' => 'Walkingm',
                'action' => 'walkingm',
                'charge_field' => 'walkingm_charge_value',
                'fields' => [
                    ['name' => 'walkingm_client_id', 'label' => 'Client ID', 'column' => 'api_publishable_key', 'required' => true],
                    ['name' => 'walkingm_client_secret', 'label' => 'Client Secret', 'column' => 'api_secret_key', 'required' => true],
                ],
            ],
            'mollie' => [
                'label' => 'Mollie',
                'action' => 'mollie',
                'charge_field' => 'mollie_charge_value',
                'fields' => [
                    ['name' => 'mollie_api_key', 'label' => 'API Key', 'column' => 'api_publishable_key', 'required' => true],
                ],
            ],
            'cashfree' => [
                'label' => 'Cashfree',
                'action' => 'cashfree',
                'charge_field' => 'cashfree_charge_value',
                'fields' => [
                    ['name' => 'cashfree_app_id', 'label' => 'App ID', 'column' => 'api_publishable_key', 'required' => true],
                    ['name' => 'cashfree_secret_key', 'label' => 'Secret Key', 'column' => 'api_secret_key', 'required' => true],
                ],
            ],
            'payfast' => [
                'label' => 'Payfast',
                'action' => 'payfast',
                'charge_field' => 'payfast_charge_value',
                'fields' => [
                    ['name' => 'payfast_api_publishable_key', 'label' => 'Merchant ID', 'column' => 'api_publishable_key', 'required' => true],
                    ['name' => 'payfast_api_secret_key', 'label' => 'Merchant Key', 'column' => 'api_secret_key', 'required' => true],
                    ['name' => 'payfast_salt', 'label' => 'Security Passphrase', 'column' => 'salt', 'required' => true],
                ],
            ],
            'toyyibpay' => [
                'label' => 'toyyibPay',
                'action' => 'toyyibPay',
                'charge_field' => 'toyyibpay_charge_value',
                'fields' => [
                    ['name' => 'toyyibpay_api_secret_key', 'label' => 'Secret Key', 'column' => 'api_secret_key', 'required' => true],
                    ['name' => 'toyyibpay_category_code', 'label' => 'Category Code', 'column' => 'api_signature', 'required' => true],
                ],
            ],
            'twocheckout' => [
                'label' => '2Checkout',
                'action' => 'twocheckout',
                'charge_field' => 'twocheckout_charge_value',
                'fields' => [
                    ['name' => 'twocheckout_api_publishable_key', 'label' => 'Merchant Code', 'column' => 'api_publishable_key', 'required' => true],
                    ['name' => 'twocheckout_api_secret_key', 'label' => 'Secret Key', 'column' => 'api_secret_key', 'required' => true],
                ],
            ],
            'skrill' => [
                'label' => 'Skrill',
                'action' => 'skrill',
                'charge_field' => 'skrill_charge_value',
                'fields' => [
                    ['name' => 'skrill_api_email', 'label' => 'Merchant Account Email', 'column' => 'api_email', 'required' => true],
                    ['name' => 'skrill_salt', 'label' => 'Merchant Secret Salt', 'column' => 'salt', 'required' => true],
                ],
            ],
            'payhere' => [
                'label' => 'PayHere',
                'action' => 'payhere',
                'charge_field' => 'payhere_charge_value',
                'fields' => [
                    ['name' => 'payhere_api_publishable_key', 'label' => 'Merchant ID', 'column' => 'api_publishable_key', 'required' => true],
                    ['name' => 'payhere_api_secret_key', 'label' => 'Merchant Secret', 'column' => 'api_secret_key', 'required' => true],
                ],
            ],
            'onepay' => [
                'label' => 'Onepay',
                'action' => 'onepay',
                'charge_field' => 'onepay_charge_value',
                'fields' => [
                    ['name' => 'onepay_merchant_id', 'label' => 'Merchant ID', 'column' => 'api_publishable_key', 'required' => true],
                    ['name' => 'onepay_salt', 'label' => 'Access Code', 'column' => 'salt', 'required' => true],
                    ['name' => 'onepay_api_signature', 'label' => 'Hash Key', 'column' => 'api_signature', 'required' => true],
                ],
            ],
            'dpopay' => [
                'label' => 'DPO Pay',
                'action' => 'dpopay',
                'charge_field' => 'dpopay_charge_value',
                'fields' => [
                    ['name' => 'dpopay_company_token', 'label' => 'Company Token', 'column' => 'api_secret_key', 'required' => true],
                ],
            ],
            'momopay' => [
                'label' => 'MoMo Pay',
                'action' => 'momopay',
                'charge_field' => 'momopay_charge_value',
                'fields' => [
                    ['name' => 'subscriptionKey', 'label' => 'Subscription Key', 'column' => 'api_secret_key', 'required' => true],
                    ['name' => 'apiKey', 'label' => 'API Key', 'column' => 'api_publishable_key', 'required' => true],
                    ['name' => 'userId', 'label' => 'User ID', 'column' => 'api_username', 'required' => true],
                ],
            ],
            'kowri' => [
                'label' => 'Kowri',
                'action' => 'kowri',
                'hidden' => true,
                'charge_field' => 'kowri_charge_value',
                'fields' => [
                    ['name' => 'kowri_app_id', 'label' => 'App ID', 'column' => 'api_publishable_key', 'required' => true],
                    ['name' => 'kowri_app_reference', 'label' => 'App Reference', 'column' => 'api_username', 'required' => true],
                    ['name' => 'kowri_secret', 'label' => 'Secret', 'column' => 'api_secret_key', 'required' => true],
                ],
            ],
        ];
    }

    public function typeForAction(string $action): ?string
    {
        if ($action === 'toyyibpay') {
            return 'toyyibpay';
        }

        foreach ($this->gateways() as $type => $gateway) {
            if ($gateway['action'] === $action || $type === $action) {
                return $type;
            }
        }

        return null;
    }

    /**
     * @return array<string, PaymentSetting>
     */
    public function keyedByType(): array
    {
        $rows = [];
        foreach (PaymentSetting::query()->orderBy('id')->get() as $row) {
            $rows[(string) $row->payment_type] = $row;
        }

        return $rows;
    }

    public function activeType(): string
    {
        $type = PaymentSetting::query()->where('is_active', 'yes')->value('payment_type');

        return $type ? (string) $type : 'none';
    }

    public function activeMethod(): ?PaymentSetting
    {
        return PaymentSetting::query()->where('is_active', 'yes')->first();
    }

    /**
     * @param  array<string, mixed>  $input
     */
    public function save(string $type, array $input): PaymentSetting
    {
        $gateway = $this->gateways()[$type];
        $payload = array_merge($gateway['extra'] ?? [], [
            'payment_type' => $type,
            'charge_type' => (string) ($input['charge_type'] ?? 'none'),
            'charge_value' => $input[$gateway['charge_field']] ?? $input['charge_value'] ?? null,
        ]);

        foreach ($gateway['fields'] as $field) {
            $payload[$field['column']] = (string) ($input[$field['name']] ?? '');
        }

        $row = PaymentSetting::query()->where('payment_type', $type)->first();
        if ($row) {
            $row->fill($payload)->save();

            return $row->fresh();
        }

        return PaymentSetting::query()->create(array_merge($this->insertDefaults(), $payload));
    }

    public function exists(string $paymentType): bool
    {
        return PaymentSetting::query()->where('payment_type', $paymentType)->exists();
    }

    public function activate(string $paymentSetting): void
    {
        if ($paymentSetting === 'none') {
            PaymentSetting::query()->update(['is_active' => 'no']);

            return;
        }

        PaymentSetting::query()->where('payment_type', $paymentSetting)->update(['is_active' => 'yes']);
        PaymentSetting::query()->where('payment_type', '!=', $paymentSetting)->update(['is_active' => 'no']);
    }

    /**
     * CI payment_gateway_config — processing fee on one gateway, cleared on others.
     *
     * @param  array<string, mixed>  $input
     */
    public function saveGatewayConfig(array $input): void
    {
        $accountType = (string) ($input['account_type'] ?? 'none');
        if ($accountType === 'none') {
            PaymentSetting::query()->update([
                'charge_type' => null,
                'charge_value' => null,
            ]);

            return;
        }

        $paymentType = (string) ($input['payment_setting'] ?? '');
        PaymentSetting::query()->where('payment_type', $paymentType)->update([
            'charge_type' => $accountType,
            'charge_value' => $input['fine_amount'] ?? null,
            'payment_type' => $paymentType,
        ]);
        PaymentSetting::query()->where('payment_type', '!=', $paymentType)->update([
            'charge_type' => null,
            'charge_value' => null,
        ]);
    }

    /**
     * CI dump rows exist per gateway; inserts still need NOT NULL columns.
     *
     * @return array<string, mixed>
     */
    protected function insertDefaults(): array
    {
        return [
            'api_username' => '',
            'api_secret_key' => '',
            'salt' => '',
            'api_publishable_key' => '',
            'api_password' => '',
            'api_signature' => '',
            'api_email' => '',
            'paypal_demo' => 'TRUE',
            'account_no' => '',
            'is_active' => 'no',
            'gateway_mode' => 0,
            'paytm_website' => '',
            'paytm_industrytype' => '',
        ];
    }
}
