<?php

use App\Modules\Inventory\Controllers\ModuleStatusController;
use Illuminate\Support\Facades\Route;

Route::get('migration-status/inventory', [ModuleStatusController::class, 'status'])->name('inventory.migration_status');