<?php

use App\Modules\Payroll\Controllers\ModuleStatusController;
use Illuminate\Support\Facades\Route;

Route::get('migration-status/payroll', [ModuleStatusController::class, 'status'])->name('payroll.migration_status');