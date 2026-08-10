<?php

use App\Modules\Certificates\Controllers\ModuleStatusController;
use Illuminate\Support\Facades\Route;

Route::get('migration-status/certificates', [ModuleStatusController::class, 'status'])->name('certificates.migration_status');