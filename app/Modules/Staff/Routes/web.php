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
});
