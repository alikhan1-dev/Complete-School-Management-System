<?php

use App\Modules\Exams\Controllers\AdmitcardTemplateController;
use App\Modules\Exams\Controllers\ExamExamStudentAssignController;
use App\Modules\Exams\Controllers\ExamGroupAssignController;
use App\Modules\Exams\Controllers\ExamGroupController;
use App\Modules\Exams\Controllers\ExamGroupExamController;
use App\Modules\Exams\Controllers\ExamGroupExamSubjectController;
use App\Modules\Exams\Controllers\ExamLinkController;
use App\Modules\Exams\Controllers\ExamMarksController;
use App\Modules\Exams\Controllers\ExamPrintController;
use App\Modules\Exams\Controllers\MarksheetTemplateController;
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

    // Assign students to exam group (CI admin/examgroup/assign + addstudent)
    Route::match(['get', 'post'], 'admin/examgroup/assign/{groupId}', [ExamGroupAssignController::class, 'assign'])->name('exams.exam_groups.assign');
    Route::post('admin/examgroup/addstudent/{groupId}', [ExamGroupAssignController::class, 'save'])->name('exams.exam_groups.assign_save');

    // Assign students to a batch exam (CI examstudent + entrystudents)
    Route::match(['get', 'post'], 'admin/examgroup/assignexam/{examId}', [ExamExamStudentAssignController::class, 'assign'])->name('exams.exam_students.assign');
    Route::post('admin/examgroup/entrystudents/{examId}', [ExamExamStudentAssignController::class, 'save'])->name('exams.exam_students.assign_save');

    // Exam marks entry (CI subjectstudent + entrymarks)
    Route::match(['get', 'post'], 'admin/examgroup/marks/{examId}', [ExamMarksController::class, 'index'])->name('exams.exam_marks.index');
    Route::post('admin/examgroup/entrymarks/{examId}', [ExamMarksController::class, 'save'])->name('exams.exam_marks.save');

    // Link exams within a group (CI connectexams + ajaxConnectForm)
    Route::get('admin/examgroup/link/{groupId}', [ExamLinkController::class, 'index'])->name('exams.exam_links.index');
    Route::post('admin/examgroup/link/{groupId}', [ExamLinkController::class, 'save'])->name('exams.exam_links.save');
    Route::post('admin/examgroup/link/{groupId}/reset', [ExamLinkController::class, 'reset'])->name('exams.exam_links.reset');

    // Design marksheet templates (CI admin/marksheet)
    Route::get('admin/marksheet', [MarksheetTemplateController::class, 'index'])->name('exams.marksheet_templates.index');
    Route::get('admin/marksheet/index', [MarksheetTemplateController::class, 'index']);
    Route::post('admin/marksheet', [MarksheetTemplateController::class, 'store'])->name('exams.marksheet_templates.store');
    Route::get('admin/marksheet/edit/{id}', [MarksheetTemplateController::class, 'edit'])->name('exams.marksheet_templates.edit');
    Route::post('admin/marksheet/edit/{id}', [MarksheetTemplateController::class, 'update'])->name('exams.marksheet_templates.update');
    Route::get('admin/marksheet/delete/{id}', [MarksheetTemplateController::class, 'destroy'])->name('exams.marksheet_templates.destroy');

    // Design admit card templates (CI admin/admitcard)
    Route::get('admin/admitcard', [AdmitcardTemplateController::class, 'index'])->name('exams.admitcard_templates.index');
    Route::get('admin/admitcard/index', [AdmitcardTemplateController::class, 'index']);
    Route::post('admin/admitcard', [AdmitcardTemplateController::class, 'store'])->name('exams.admitcard_templates.store');
    Route::get('admin/admitcard/edit/{id}', [AdmitcardTemplateController::class, 'edit'])->name('exams.admitcard_templates.edit');
    Route::post('admin/admitcard/edit/{id}', [AdmitcardTemplateController::class, 'update'])->name('exams.admitcard_templates.update');
    Route::get('admin/admitcard/delete/{id}', [AdmitcardTemplateController::class, 'destroy'])->name('exams.admitcard_templates.destroy');
    Route::get('admin/admitcard/activate/{id}', [AdmitcardTemplateController::class, 'activate'])->name('exams.admitcard_templates.activate');

    // Print marksheet / admit card (CI admin/examresult/*) — HTML print; mPDF/email deferred
    Route::match(['get', 'post'], 'admin/examresult/marksheet', [ExamPrintController::class, 'marksheet'])->name('exams.print.marksheet');
    Route::match(['get', 'post'], 'admin/examresult/admitcard', [ExamPrintController::class, 'admitcard'])->name('exams.print.admitcard');
    Route::get('admin/examresult/printmarksheet/{examStudentId}', [ExamPrintController::class, 'printMarksheet'])->name('exams.print.marksheet_view');
    Route::get('admin/examresult/printadmitcard/{examStudentId}', [ExamPrintController::class, 'printAdmitcard'])->name('exams.print.admitcard_view');
    Route::get('admin/examgroup/examsbygroup/{groupId}', [ExamPrintController::class, 'examsByGroup'])->name('exams.exams_by_group');
});
