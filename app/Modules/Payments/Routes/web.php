<?php

use App\Modules\Payments\Controllers\ModuleStatusController;
use Illuminate\Support\Facades\Route;

Route::get('migration-status/payments', [ModuleStatusController::class, 'status'])->name('payments.migration_status');