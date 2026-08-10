<?php

use App\Modules\FrontOffice\Controllers\ModuleStatusController;
use Illuminate\Support\Facades\Route;

Route::get('migration-status/frontoffice', [ModuleStatusController::class, 'status'])->name('frontoffice.migration_status');