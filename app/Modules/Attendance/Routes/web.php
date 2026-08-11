<?php

use App\Modules\Attendance\Controllers\ModuleStatusController;
use App\Modules\Attendance\Controllers\StuAttendenceController;
use Illuminate\Support\Facades\Route;

Route::get('migration-status/attendance', [ModuleStatusController::class, 'status'])->name('attendance.migration_status');

Route::middleware(['staff.auth'])->group(function () {
    // CI admin/stuattendence — student day attendance
    Route::match(['get', 'post'], 'admin/stuattendence', [StuAttendenceController::class, 'index'])->name('attendance.stuattendence.index');
    Route::match(['get', 'post'], 'admin/stuattendence/index', [StuAttendenceController::class, 'index']);
});
