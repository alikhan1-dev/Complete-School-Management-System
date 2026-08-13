<?php

use App\Modules\Exams\Controllers\ExamGroupController;
use App\Modules\Exams\Controllers\ExamGroupExamController;
use App\Modules\Exams\Controllers\ExamGroupExamSubjectController;
use App\Modules\Exams\Controllers\ModuleStatusController;
use Illuminate\Support\Facades\Route;

Route::get('migration-status/exams', [ModuleStatusController::class, 'status'])->name('exams.migration_status');

Route::middleware(['staff.auth'])->group(function () {
    // Exam groups (CI admin/examgroup)
    Route::get('admin/examgroup', [ExamGroupController::class, 'index'])->name('exams.exam_groups.index');
    Route::get('admin/examgroup/index', [ExamGroupController::class, 'index']);
    Route::post('admin/examgroup', [ExamGroupController::class, 'store'])->name('exams.exam_groups.store');
    Route::post('admin/examgroup/index', [ExamGroupController::class, 'store']);
    Route::get('admin/examgroup/edit/{id}', [ExamGroupController::class, 'edit'])->name('exams.exam_groups.edit');
    Route::post('admin/examgroup/edit/{id}', [ExamGroupController::class, 'update'])->name('exams.exam_groups.update');
    Route::get('admin/examgroup/delete/{id}', [ExamGroupController::class, 'destroy'])->name('exams.exam_groups.destroy');

    // Exams within a group (CI admin/examgroup/addexam/{id})
    Route::get('admin/examgroup/addexam/{groupId}', [ExamGroupExamController::class, 'index'])->name('exams.exam_group_exams.index');
    Route::post('admin/examgroup/addexam/{groupId}', [ExamGroupExamController::class, 'store'])->name('exams.exam_group_exams.store');
    Route::get('admin/examgroup/addexam/{groupId}/edit/{id}', [ExamGroupExamController::class, 'edit'])->name('exams.exam_group_exams.edit');
    Route::post('admin/examgroup/addexam/{groupId}/edit/{id}', [ExamGroupExamController::class, 'update'])->name('exams.exam_group_exams.update');
    Route::get('admin/examgroup/addexam/{groupId}/delete/{id}', [ExamGroupExamController::class, 'destroy'])->name('exams.exam_group_exams.destroy');

    // Exam subjects on a batch exam (CI getexamSubjects + addexamsubject)
    Route::get('admin/examgroup/examsubject/{examId}', [ExamGroupExamSubjectController::class, 'index'])->name('exams.exam_subjects.index');
    Route::post('admin/examgroup/examsubject/{examId}', [ExamGroupExamSubjectController::class, 'store'])->name('exams.exam_subjects.store');
    Route::get('admin/examgroup/examsubject/{examId}/edit/{id}', [ExamGroupExamSubjectController::class, 'edit'])->name('exams.exam_subjects.edit');
    Route::post('admin/examgroup/examsubject/{examId}/edit/{id}', [ExamGroupExamSubjectController::class, 'update'])->name('exams.exam_subjects.update');
    Route::get('admin/examgroup/examsubject/{examId}/delete/{id}', [ExamGroupExamSubjectController::class, 'destroy'])->name('exams.exam_subjects.destroy');
});
