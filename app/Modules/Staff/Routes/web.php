<?php

use App\Modules\Staff\Controllers\ModuleStatusController;
use App\Modules\Staff\Controllers\StaffController;
use Illuminate\Support\Facades\Route;

Route::get('migration-status/staff', [ModuleStatusController::class, 'status'])->name('staff.migration_status');

Route::middleware(['staff.auth'])->prefix('admin')->group(function () {
    Route::get('staff', [StaffController::class, 'index'])->middleware('permission:staff,can_view')->name('staff.index');
    Route::get('staff/datatable', [StaffController::class, 'datatable'])->middleware('permission:staff,can_view')->name('staff.datatable');
    Route::get('staff/create', [StaffController::class, 'create'])->middleware('permission:staff,can_add')->name('staff.create');
    Route::post('staff/create', [StaffController::class, 'store'])->middleware('permission:staff,can_add')->name('staff.store');
    Route::get('staff/edit/{id}', [StaffController::class, 'edit'])->middleware('permission:staff,can_edit')->name('staff.edit');
    Route::post('staff/edit/{id}', [StaffController::class, 'update'])->middleware('permission:staff,can_edit')->name('staff.update');
    Route::get('staff/profile/{id}', [StaffController::class, 'profile'])->name('staff.profile');
    Route::post('staff/ajax_attendance', [StaffController::class, 'ajaxAttendance'])->name('staff.ajax_attendance');
    Route::post('staff/disablestaff/{id}', [StaffController::class, 'disableStaff'])->middleware('permission:disable_staff,can_view')->name('staff.disable');
    Route::get('staff/enablestaff/{id}', [StaffController::class, 'enableStaff'])->name('staff.enable');
});
