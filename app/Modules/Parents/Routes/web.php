<?php

use App\Modules\Parents\Controllers\ModuleStatusController;
use Illuminate\Support\Facades\Route;

Route::get('migration-status/parents', [ModuleStatusController::class, 'status'])->name('parents.migration_status');