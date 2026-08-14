<?php

use App\Modules\Settings\Controllers\CaptchaSettingController;
use App\Modules\Settings\Controllers\ModuleStatusController;
use Illuminate\Support\Facades\Route;

Route::get('migration-status/settings', [ModuleStatusController::class, 'status'])->name('settings.migration_status');

Route::middleware(['staff.auth'])->group(function () {
    Route::get('admin/captcha', [CaptchaSettingController::class, 'index']);
    Route::post('admin/captcha/changeStatus', [CaptchaSettingController::class, 'changeStatus']);
});