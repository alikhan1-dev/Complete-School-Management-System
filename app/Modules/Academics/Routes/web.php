<?php

use App\Modules\Academics\Controllers\ModuleStatusController;
use Illuminate\Support\Facades\Route;

Route::get('migration-status/academics', [ModuleStatusController::class, 'status'])->name('academics.migration_status');