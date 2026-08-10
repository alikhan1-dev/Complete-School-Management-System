<?php

use App\Modules\Students\Controllers\ModuleStatusController;
use Illuminate\Support\Facades\Route;

Route::get('migration-status/students', [ModuleStatusController::class, 'status'])->name('students.migration_status');