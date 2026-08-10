<?php

use App\Modules\Exams\Controllers\ModuleStatusController;
use Illuminate\Support\Facades\Route;

Route::get('migration-status/exams', [ModuleStatusController::class, 'status'])->name('exams.migration_status');