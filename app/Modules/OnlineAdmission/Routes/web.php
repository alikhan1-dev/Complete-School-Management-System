<?php

use App\Modules\OnlineAdmission\Controllers\ModuleStatusController;
use Illuminate\Support\Facades\Route;

Route::get('migration-status/onlineadmission', [ModuleStatusController::class, 'status'])->name('onlineadmission.migration_status');