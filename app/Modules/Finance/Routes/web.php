<?php

use App\Modules\Finance\Controllers\ModuleStatusController;
use Illuminate\Support\Facades\Route;

Route::get('migration-status/finance', [ModuleStatusController::class, 'status'])->name('finance.migration_status');