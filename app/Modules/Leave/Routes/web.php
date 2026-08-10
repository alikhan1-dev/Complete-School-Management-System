<?php

use App\Modules\Leave\Controllers\ModuleStatusController;
use Illuminate\Support\Facades\Route;

Route::get('migration-status/leave', [ModuleStatusController::class, 'status'])->name('leave.migration_status');