<?php

use App\Modules\Library\Controllers\ModuleStatusController;
use Illuminate\Support\Facades\Route;

Route::get('migration-status/library', [ModuleStatusController::class, 'status'])->name('library.migration_status');