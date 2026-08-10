<?php

use App\Modules\Timetable\Controllers\ModuleStatusController;
use Illuminate\Support\Facades\Route;

Route::get('migration-status/timetable', [ModuleStatusController::class, 'status'])->name('timetable.migration_status');