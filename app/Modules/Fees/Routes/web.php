<?php

use App\Modules\Fees\Controllers\ModuleStatusController;
use Illuminate\Support\Facades\Route;

Route::get('migration-status/fees', [ModuleStatusController::class, 'status'])->name('fees.migration_status');