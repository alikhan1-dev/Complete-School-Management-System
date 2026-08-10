<?php

use App\Modules\Settings\Controllers\ModuleStatusController;
use Illuminate\Support\Facades\Route;

Route::get('migration-status/settings', [ModuleStatusController::class, 'status'])->name('settings.migration_status');