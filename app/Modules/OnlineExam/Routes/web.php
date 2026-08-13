<?php

use App\Modules\OnlineExam\Controllers\ModuleStatusController;
use App\Modules\OnlineExam\Controllers\OnlineExamAssignController;
use App\Modules\OnlineExam\Controllers\OnlineExamController;
use App\Modules\OnlineExam\Controllers\OnlineExamQuestionController;
use App\Modules\OnlineExam\Controllers\OnlineExamResultController;
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

    // Attach questions to exam (CI questionAdd / deleteExamQuestions) — AJAX toggle deferred
    Route::get('admin/onlineexam/questions/{examId}', [OnlineExamQuestionController::class, 'index'])->name('onlineexam.exam_questions.index');
    Route::post('admin/onlineexam/questions/{examId}', [OnlineExamQuestionController::class, 'attach'])->name('onlineexam.exam_questions.attach');
    Route::post('admin/onlineexam/questions/{examId}/marks/{id}', [OnlineExamQuestionController::class, 'updateMarks'])->name('onlineexam.exam_questions.update_marks');
    Route::get('admin/onlineexam/questions/{examId}/detach/{id}', [OnlineExamQuestionController::class, 'detach'])->name('onlineexam.exam_questions.detach');

    // Assign students (CI admin/onlineexam/assign + addstudent)
    Route::match(['get', 'post'], 'admin/onlineexam/assign/{examId}', [OnlineExamAssignController::class, 'assign'])->name('onlineexam.assign.index');
    Route::post('admin/onlineexam/addstudent/{examId}', [OnlineExamAssignController::class, 'save'])->name('onlineexam.assign.save');

    // Results + descriptive evaluation (CI evalution / getstudentresult / fillmarks)
    Route::get('admin/onlineexam/results/{examId}', [OnlineExamResultController::class, 'index'])->name('onlineexam.results.index');
    Route::get('admin/onlineexam/studentresult/{examId}/{onlineexamStudentId}', [OnlineExamResultController::class, 'studentResult'])->name('onlineexam.results.student');
    Route::match(['get', 'post'], 'admin/onlineexam/evalution/{examId}', [OnlineExamResultController::class, 'evaluation'])->name('onlineexam.evaluation.index');
    Route::post('admin/onlineexam/fillmarks/{examId}', [OnlineExamResultController::class, 'fillMarks'])->name('onlineexam.evaluation.fillmarks');
    Route::get('admin/onlineexam/downloadattachment/{doc}', [OnlineExamResultController::class, 'downloadAttachment'])->name('onlineexam.results.attachment');
});
