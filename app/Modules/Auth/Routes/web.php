<?php

use App\Modules\Auth\Controllers\PasswordResetController;
use App\Modules\Auth\Controllers\StaffLoginController;
use App\Modules\Auth\Controllers\StudentParentLoginController;
use Illuminate\Support\Facades\Route;

Route::get('site/login', [StaffLoginController::class, 'showLoginForm'])->name('staff.login');
Route::post('site/login', [StaffLoginController::class, 'login'])->name('staff.login.submit');
Route::post('site/logout', [StaffLoginController::class, 'logout'])->name('staff.logout');

Route::get('site/forgotpassword', [PasswordResetController::class, 'showStaffForgotForm'])->name('staff.forgot_password');
Route::post('site/forgotpassword', [PasswordResetController::class, 'sendStaffResetLink'])->name('staff.forgot_password.submit');
Route::get('admin/resetpassword/{verification_code}', [PasswordResetController::class, 'showStaffResetForm'])->name('staff.reset_password');
Route::post('admin/resetpassword/{verification_code}', [PasswordResetController::class, 'resetStaffPassword'])->name('staff.reset_password.submit');

Route::get('site/userlogin', [StudentParentLoginController::class, 'showLoginForm'])->name('student_parent.login');
Route::post('site/userlogin', [StudentParentLoginController::class, 'login'])->name('student_parent.login.submit');
Route::post('site/userlogout', [StudentParentLoginController::class, 'logout'])->name('student_parent.logout');

Route::get('site/ufpassword', [PasswordResetController::class, 'showPortalForgotForm'])->name('student_parent.forgot_password');
Route::post('site/ufpassword', [PasswordResetController::class, 'sendPortalResetLink'])->name('student_parent.forgot_password.submit');
Route::get('user/resetpassword/{role}/{verification_code}', [PasswordResetController::class, 'showPortalResetForm'])->name('student_parent.reset_password');
Route::post('user/resetpassword/{role}/{verification_code}', [PasswordResetController::class, 'resetPortalPassword'])->name('student_parent.reset_password.submit');

Route::middleware(['student_parent.auth'])->group(function () {
    Route::get('user/user/choose', [StudentParentLoginController::class, 'chooseClass'])->name('student_parent.choose_class');
    Route::post('user/user/choose', [StudentParentLoginController::class, 'storeClass'])->name('student_parent.choose_class.store');
});
