<?php

use App\Modules\Homework\Controllers\HomeworkController;
use App\Modules\Homework\Controllers\HomeworkEvaluationController;
use App\Modules\Homework\Controllers\ModuleStatusController;
use App\Modules\Homework\Controllers\StudentHomeworkController;
use Illuminate\Support\Facades\Route;

Route::get('migration-status/homework', [ModuleStatusController::class, 'status'])->name('homework.migration_status');

Route::middleware([
    'student_parent.auth',
    'student_parent.login_token',
    'student_parent.selected_class',
    'student_parent.permission:homework',
])->group(function () {
    // CI user/homework — portal first slice (daily assignment deferred)
    Route::get('user/homework', [StudentHomeworkController::class, 'index'])->name('user.homework.index');
    Route::get('user/homework/index', [StudentHomeworkController::class, 'index']);
    Route::get('user/homework/view/{id}', [StudentHomeworkController::class, 'view'])
        ->whereNumber('id')
        ->name('user.homework.view');
    // CI alias for detail
    Route::get('user/homework/homework_detail/{id}/{status?}', [StudentHomeworkController::class, 'view'])
        ->whereNumber('id')
        ->name('user.homework.detail');
    Route::post('user/homework/upload_docs', [StudentHomeworkController::class, 'submit'])->name('user.homework.submit');
    Route::get('user/homework/download/{id}', [StudentHomeworkController::class, 'download'])
        ->whereNumber('id')
        ->name('user.homework.download');
    Route::get('user/homework/assigmnetDownload/{id}', [StudentHomeworkController::class, 'downloadAssignment'])
        ->whereNumber('id')
        ->name('user.homework.assignment');
});

Route::middleware(['staff.auth'])->group(function () {
    // CI Homework — admin CRUD + evaluation (daily / reports deferred)
    Route::get('homework', [HomeworkController::class, 'index'])->name('homework.index');
    Route::get('homework/index', [HomeworkController::class, 'index']);
    Route::get('homework/create', [HomeworkController::class, 'create'])->name('homework.create');
    Route::post('homework/create', [HomeworkController::class, 'store'])->name('homework.store');
    Route::get('homework/edit/{id}', [HomeworkController::class, 'edit'])->whereNumber('id')->name('homework.edit');
    Route::post('homework/edit/{id}', [HomeworkController::class, 'update'])->whereNumber('id')->name('homework.update');
    Route::get('homework/delete/{id}', [HomeworkController::class, 'destroy'])->whereNumber('id')->name('homework.destroy');
    Route::get('homework/download/{id}', [HomeworkController::class, 'download'])->whereNumber('id')->name('homework.download');

    Route::get('homework/evaluation/{id}', [HomeworkEvaluationController::class, 'show'])
        ->whereNumber('id')
        ->name('homework.evaluation');
    Route::post('homework/add_evaluation', [HomeworkEvaluationController::class, 'store'])
        ->name('homework.evaluation.store');
    // CI typo preserved as alias — admin uses submit_assignment.id
    Route::get('homework/assigmnetDownload/{id}', [HomeworkEvaluationController::class, 'downloadAssignment'])
        ->whereNumber('id')
        ->name('homework.assignment.download');
});
