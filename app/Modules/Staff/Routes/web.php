<?php

use App\Modules\Staff\Controllers\StaffController;
use Illuminate\Support\Facades\Route;

Route::middleware(['staff.auth'])->prefix('admin')->group(function () {
    Route::get('staff', [StaffController::class, 'index'])->middleware('permission:staff,can_view')->name('staff.index');
    Route::get('staff/datatable', [StaffController::class, 'datatable'])->middleware('permission:staff,can_view')->name('staff.datatable');
});
