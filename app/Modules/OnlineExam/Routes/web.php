<?php

use App\Modules\OnlineExam\Controllers\ModuleStatusController;
use App\Modules\OnlineExam\Controllers\OnlineExamController;
use App\Modules\OnlineExam\Controllers\QuestionBankController;
use Illuminate\Support\Facades\Route;

Route::get('migration-status/onlineexam', [ModuleStatusController::class, 'status'])->name('onlineexam.migration_status');

Route::middleware(['staff.auth'])->group(function () {
    // Question bank (CI admin/question) — CSV import / CMS images deferred
    Route::get('admin/question', [QuestionBankController::class, 'index'])->name('onlineexam.questions.index');
    Route::get('admin/question/index', [QuestionBankController::class, 'index']);
    Route::post('admin/question', [QuestionBankController::class, 'store'])->name('onlineexam.questions.store');
    Route::get('admin/question/read/{id}', [QuestionBankController::class, 'read'])->name('onlineexam.questions.read');
    Route::get('admin/question/edit/{id}', [QuestionBankController::class, 'edit'])->name('onlineexam.questions.edit');
    Route::post('admin/question/edit/{id}', [QuestionBankController::class, 'update'])->name('onlineexam.questions.update');
    Route::get('admin/question/delete/{id}', [QuestionBankController::class, 'destroy'])->name('onlineexam.questions.destroy');

    // Online examinations (CI admin/onlineexam) — attach/assign/portal deferred
    Route::get('admin/onlineexam', [OnlineExamController::class, 'index'])->name('onlineexam.exams.index');
    Route::get('admin/onlineexam/index', [OnlineExamController::class, 'index']);
    Route::post('admin/onlineexam', [OnlineExamController::class, 'store'])->name('onlineexam.exams.store');
    Route::get('admin/onlineexam/edit/{id}', [OnlineExamController::class, 'edit'])->name('onlineexam.exams.edit');
    Route::post('admin/onlineexam/edit/{id}', [OnlineExamController::class, 'update'])->name('onlineexam.exams.update');
    Route::get('admin/onlineexam/delete/{id}', [OnlineExamController::class, 'destroy'])->name('onlineexam.exams.destroy');
});
