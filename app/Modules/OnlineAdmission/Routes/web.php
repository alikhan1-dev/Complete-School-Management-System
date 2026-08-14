<?php

use App\Modules\OnlineAdmission\Controllers\ModuleStatusController;
use App\Modules\OnlineAdmission\Controllers\OnlineAdmissionPublicController;
use App\Modules\OnlineAdmission\Controllers\OnlineAdmissionSettingController;
use App\Modules\OnlineAdmission\Controllers\OnlineStudentController;
use Illuminate\Support\Facades\Route;

Route::get('migration-status/onlineadmission', [ModuleStatusController::class, 'status'])->name('onlineadmission.migration_status');

Route::get('online_admission', [OnlineAdmissionPublicController::class, 'admission']);
Route::post('online_admission', [OnlineAdmissionPublicController::class, 'admission']);
Route::get('welcome/admission', [OnlineAdmissionPublicController::class, 'admission']);
Route::post('welcome/admission', [OnlineAdmissionPublicController::class, 'admission']);
Route::get('welcome/online_admission_review/{reference_no}', [OnlineAdmissionPublicController::class, 'review']);
Route::match(['get', 'post'], 'welcome/editonlineadmission/{reference_no}', [OnlineAdmissionPublicController::class, 'editonlineadmission']);
Route::post('welcome/checkadmissionstatus', [OnlineAdmissionPublicController::class, 'checkadmissionstatus']);
Route::post('welcome/submitadmission', [OnlineAdmissionPublicController::class, 'submitadmission']);
Route::post('welcome/getSections', [OnlineAdmissionPublicController::class, 'getSections']);
Route::get('welcome/download/{id}', [OnlineAdmissionPublicController::class, 'download'])->whereNumber('id');
Route::post('site/refreshCaptcha', [OnlineAdmissionPublicController::class, 'refreshCaptcha']);

Route::middleware(['staff.auth'])->group(function () {
    Route::match(['get', 'post'], 'admin/onlineadmission/admissionsetting', [OnlineAdmissionSettingController::class, 'admissionsetting'])
        ->name('onlineadmission.settings');
    Route::post('admin/onlineadmission/changeformfieldsetting', [OnlineAdmissionSettingController::class, 'changeformfieldsetting']);
    Route::get('admin/onlineadmission/download/{id}', [OnlineAdmissionSettingController::class, 'download'])
        ->whereNumber('id');

    Route::get('admin/onlinestudent', [OnlineStudentController::class, 'index'])->name('onlineadmission.students.index');
    Route::get('admin/onlinestudent/index', [OnlineStudentController::class, 'index']);
    Route::match(['get', 'post'], 'admin/onlinestudent/edit/{id}', [OnlineStudentController::class, 'edit'])
        ->whereNumber('id');
    Route::get('admin/onlinestudent/delete/{id}', [OnlineStudentController::class, 'delete'])
        ->whereNumber('id');
    Route::post('admin/onlinestudent/getByClass', [OnlineStudentController::class, 'getByClass']);
    Route::post('admin/onlinestudent/checkpaymentstatus', [OnlineStudentController::class, 'checkpaymentstatus']);
});
