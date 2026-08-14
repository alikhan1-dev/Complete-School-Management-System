<?php

use App\Modules\Communication\Controllers\EmailConfigController;
use App\Modules\Communication\Controllers\ModuleStatusController;
use App\Modules\Communication\Controllers\SmsConfigController;
use Illuminate\Support\Facades\Route;

Route::get('migration-status/communication', [ModuleStatusController::class, 'status'])->name('communication.migration_status');

Route::middleware(['staff.auth'])->group(function () {
    // CI Emailconfig
    Route::get('emailconfig', [EmailConfigController::class, 'index'])->name('communication.emailconfig.index');
    Route::get('emailconfig/index', [EmailConfigController::class, 'index']);
    Route::post('emailconfig', [EmailConfigController::class, 'save'])->name('communication.emailconfig.save');
    Route::post('emailconfig/index', [EmailConfigController::class, 'save']);

    // CI Smsconfig
    Route::get('smsconfig', [SmsConfigController::class, 'index'])->name('communication.smsconfig.index');
    Route::get('smsconfig/index', [SmsConfigController::class, 'index']);
    Route::post('smsconfig/{action}', [SmsConfigController::class, 'save'])
        ->where('action', 'clickatell|twilio|custom|msgnineone|smscountry|textlocal|bulk_sms|smsgatewayhub|mobireach|nexmo|africastalking|smseg')
        ->name('communication.smsconfig.save');
});
