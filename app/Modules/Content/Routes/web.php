<?php

use App\Modules\Content\Controllers\ModuleStatusController;
use Illuminate\Support\Facades\Route;

Route::get('migration-status/content', [ModuleStatusController::class, 'status'])->name('content.migration_status');