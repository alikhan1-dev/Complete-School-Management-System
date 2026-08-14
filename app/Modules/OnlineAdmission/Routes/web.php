<?php

use App\Modules\OnlineAdmission\Controllers\ModuleStatusController;
use App\Modules\OnlineAdmission\Controllers\OnlineAdmissionSettingController;
use App\Modules\OnlineAdmission\Controllers\OnlineStudentController;
use Illuminate\Support\Facades\Route;

Route::get('migration-status/onlineadmission', [ModuleStatusController::class, 'status'])->name('onlineadmission.migration_status');

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
