<?php

use App\Modules\Hostel\Controllers\ModuleStatusController;
use Illuminate\Support\Facades\Route;

Route::get('migration-status/hostel', [ModuleStatusController::class, 'status'])->name('hostel.migration_status');