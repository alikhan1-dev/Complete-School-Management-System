<?php

use App\Modules\FrontCms\Controllers\ModuleStatusController;
use Illuminate\Support\Facades\Route;

Route::get('migration-status/frontcms', [ModuleStatusController::class, 'status'])->name('frontcms.migration_status');