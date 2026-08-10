<?php

use App\Modules\OnlineExam\Controllers\ModuleStatusController;
use Illuminate\Support\Facades\Route;

Route::get('migration-status/onlineexam', [ModuleStatusController::class, 'status'])->name('onlineexam.migration_status');