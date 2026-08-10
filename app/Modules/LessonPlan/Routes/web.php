<?php

use App\Modules\LessonPlan\Controllers\ModuleStatusController;
use Illuminate\Support\Facades\Route;

Route::get('migration-status/lessonplan', [ModuleStatusController::class, 'status'])->name('lessonplan.migration_status');