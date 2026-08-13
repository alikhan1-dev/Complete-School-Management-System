<?php

use App\Modules\Homework\Controllers\AdminDailyAssignmentController;
use App\Modules\Homework\Controllers\HomeworkController;
use App\Modules\Homework\Controllers\HomeworkEvaluationController;
use App\Modules\Homework\Controllers\ModuleStatusController;
use App\Modules\Homework\Controllers\StudentDailyAssignmentController;
use App\Modules\Homework\Controllers\StudentHomeworkController;
use Illuminate\Support\Facades\Route;

Route::get('migration-status/homework', [ModuleStatusController::class, 'status'])->name('homework.migration_status');

Route::middleware([
    'student_parent.auth',
    'student_parent.login_token',
    'student_parent.selected_class',
    'student_parent.permission:homework',
])->group(function () {
    // CI user/homework
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

    // CI user/homework/dailyassignment*
    Route::get('user/homework/dailyassignment', [StudentDailyAssignmentController::class, 'index'])
        ->name('user.homework.daily.index');
    Route::post('user/homework/createdailyassignment', [StudentDailyAssignmentController::class, 'store'])
        ->name('user.homework.daily.store');
    Route::get('user/homework/editdailyassignment/{id}', [StudentDailyAssignmentController::class, 'edit'])
        ->whereNumber('id')
        ->name('user.homework.daily.edit');
    Route::post('user/homework/updatedailyassignment/{id}', [StudentDailyAssignmentController::class, 'update'])
        ->whereNumber('id')
        ->name('user.homework.daily.update');
    // CI uses POST without id in path for update — keep named POST with id; optional alias:
    Route::post('user/homework/updatedailyassignment', [StudentDailyAssignmentController::class, 'updateFromBody'])
        ->name('user.homework.daily.update.body');
    Route::get('user/homework/deletedailyassignment/{id}', [StudentDailyAssignmentController::class, 'destroy'])
        ->whereNumber('id')
        ->name('user.homework.daily.destroy');
    // CI typo preserved
    Route::get('user/homework/dailyassigmnetdownload/{id}', [StudentDailyAssignmentController::class, 'download'])
        ->whereNumber('id')
        ->name('user.homework.daily.download');
});

Route::middleware(['staff.auth'])->group(function () {
    // CI Homework — admin CRUD + evaluation
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

    // CI homework/dailyassignment — list + remark (reports deferred)
    Route::get('homework/dailyassignment', [AdminDailyAssignmentController::class, 'index'])
        ->name('homework.daily.index');
    Route::get('homework/dailyassignment/evaluate/{id}', [AdminDailyAssignmentController::class, 'evaluate'])
        ->whereNumber('id')
        ->name('homework.daily.evaluate');
    Route::post('homework/submitassignmentremark', [AdminDailyAssignmentController::class, 'saveRemark'])
        ->name('homework.daily.remark');
    // CI typo preserved
    Route::get('homework/dailyassigmnetdownload/{id}', [AdminDailyAssignmentController::class, 'download'])
        ->whereNumber('id')
        ->name('homework.daily.download');
});
