<?php

namespace App\Modules\Payments\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Payments\Services\PaymentSettingService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * CI Webhooks — Instamojo MAC verification stub; settlement deferred.
 */
class WebhooksController extends Controller
{
    public function __construct(
        protected PaymentSettingService $paymentSettings,
    ) {
    }

    public function instaWebhook(Request $request): Response
    {
        $data = $request->all();
        if (! isset($data['mac'])) {
            return response('MAC mismatch', 400);
        }

        $macProvided = (string) $data['mac'];
        unset($data['mac']);

        $salt = $this->paymentSettings->instamojoWebhookSalt();
        if ($salt === null || $salt === '') {
            return response('', 200);
        }

        if (PHP_VERSION_ID >= 50400) {
            ksort($data, SORT_STRING | SORT_FLAG_CASE);
        } else {
            uksort($data, 'strcasecmp');
        }

        $macCalculated = hash_hmac('sha1', implode('|', $data), $salt);
        if (! hash_equals($macCalculated, $macProvided)) {
            return response('MAC mismatch', 400);
        }

        return response('', 200);
    }
}
