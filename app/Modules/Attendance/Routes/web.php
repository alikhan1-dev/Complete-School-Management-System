<?php

use App\Modules\Attendance\Controllers\ModuleStatusController;
use Illuminate\Support\Facades\Route;

Route::get('migration-status/attendance', [ModuleStatusController::class, 'status'])->name('attendance.migration_status');