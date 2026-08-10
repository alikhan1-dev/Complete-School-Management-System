<?php

use App\Modules\Transport\Controllers\ModuleStatusController;
use Illuminate\Support\Facades\Route;

Route::get('migration-status/transport', [ModuleStatusController::class, 'status'])->name('transport.migration_status');