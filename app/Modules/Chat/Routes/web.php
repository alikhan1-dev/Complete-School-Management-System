<?php

use App\Modules\Chat\Controllers\ModuleStatusController;
use Illuminate\Support\Facades\Route;

Route::get('migration-status/chat', [ModuleStatusController::class, 'status'])->name('chat.migration_status');