<?php

use App\Modules\Reports\Controllers\ModuleStatusController;
use Illuminate\Support\Facades\Route;

Route::get('migration-status/reports', [ModuleStatusController::class, 'status'])->name('reports.migration_status');