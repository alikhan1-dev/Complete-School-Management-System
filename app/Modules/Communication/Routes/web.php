<?php

use App\Modules\Communication\Controllers\ModuleStatusController;
use Illuminate\Support\Facades\Route;

Route::get('migration-status/communication', [ModuleStatusController::class, 'status'])->name('communication.migration_status');