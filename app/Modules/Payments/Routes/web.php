<?php

use App\Modules\Payments\Controllers\ModuleStatusController;
use App\Modules\Payments\Controllers\OnlineAdmissionCheckoutController;
use App\Modules\Payments\Controllers\PaymentSettingController;
use Illuminate\Support\Facades\Route;

Route::get('migration-status/payments', [ModuleStatusController::class, 'status'])->name('payments.migration_status');

Route::post('onlineadmission/checkout', [OnlineAdmissionCheckoutController::class, 'index']);
Route::get('onlineadmission/checkout/successinvoice/{reference_no}', [OnlineAdmissionCheckoutController::class, 'successinvoice']);
Route::get('onlineadmission/checkout/processinginvoice/{reference_no?}', [OnlineAdmissionCheckoutController::class, 'processinginvoice']);
Route::get('onlineadmission/checkout/paymentfailed/{reference_no?}', [OnlineAdmissionCheckoutController::class, 'paymentfailed']);
Route::get('onlineadmission/{gateway}', [OnlineAdmissionCheckoutController::class, 'gateway'])
    ->where('gateway', 'paypal|stripe|payu|ccavenue|instamojo|paystack|razorpay|paytm|midtrans|pesapal|flutterwave|ipayafrica|jazzcash|billplz|sslcommerz|walkingm|mollie|cashfree|payfast|toyyibpay|twocheckout|skrill|payhere|onepay|icicipay|icici|dpopay|momopay|kowri');
Route::get('onlineadmission/{gateway}/index', [OnlineAdmissionCheckoutController::class, 'gateway'])
    ->where('gateway', 'paypal|stripe|payu|ccavenue|instamojo|paystack|razorpay|paytm|midtrans|pesapal|flutterwave|ipayafrica|jazzcash|billplz|sslcommerz|walkingm|mollie|cashfree|payfast|toyyibpay|twocheckout|skrill|payhere|onepay|icicipay|icici|dpopay|momopay|kowri');

Route::middleware(['staff.auth'])->group(function () {
    Route::get('admin/paymentsettings', [PaymentSettingController::class, 'index'])->name('payments.settings.index');
    Route::get('admin/paymentsettings/index', [PaymentSettingController::class, 'index']);
    Route::post('admin/paymentsettings/setting', [PaymentSettingController::class, 'setting']);
    Route::post('admin/paymentsettings/payment_gateway_config', [PaymentSettingController::class, 'paymentGatewayConfig']);
    Route::post('admin/paymentsettings/{action}', [PaymentSettingController::class, 'save'])
        ->where('action', 'paypal|stripe|payu|ccavenue|instamojo|paystack|razorpay|paytm|midtrans|pesapal|flutterwave|ipayafrica|jazzcash|billplz|sslcommerz|walkingm|mollie|cashfree|payfast|toyyibPay|toyyibpay|twocheckout|skrill|payhere|onepay|dpopay|momopay|kowri');
});
