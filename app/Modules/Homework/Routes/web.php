<?php

use App\Modules\Homework\Controllers\ModuleStatusController;
use Illuminate\Support\Facades\Route;

Route::get('migration-status/homework', [ModuleStatusController::class, 'status'])->name('homework.migration_status');