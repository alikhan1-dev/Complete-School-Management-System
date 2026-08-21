<?php

use App\Modules\Academics\Controllers\ClassController;
use App\Modules\Academics\Controllers\CustomFieldController;
use App\Modules\Academics\Controllers\GradeController;
use App\Modules\Academics\Controllers\MarkDivisionController;
use App\Modules\Academics\Controllers\SchoolHouseController;
use App\Modules\Academics\Controllers\SectionController;
use App\Modules\Academics\Controllers\SessionController;
use App\Modules\Academics\Controllers\SubjectController;
use App\Modules\Academics\Controllers\SubjectGroupController;
use Illuminate\Support\Facades\Route;

Route::middleware(['staff.auth'])->group(function () {
    // Sessions (System Settings / Academics foundation)
    Route::get('sessions', [SessionController::class, 'index'])->name('academics.sessions.index');
    Route::get('sessions/index', [SessionController::class, 'index']);
    Route::post('sessions', [SessionController::class, 'store'])->name('academics.sessions.store');
    Route::post('sessions/create', [SessionController::class, 'store']); // CI form action
    Route::get('sessions/edit/{id}', [SessionController::class, 'edit'])->name('academics.sessions.edit');
    Route::post('sessions/edit/{id}', [SessionController::class, 'update'])->name('academics.sessions.update');
    Route::get('sessions/delete/{id}', [SessionController::class, 'destroy'])->name('academics.sessions.destroy');

    // Classes
    Route::get('classes', [ClassController::class, 'index'])->name('academics.classes.index');
    Route::post('classes', [ClassController::class, 'store'])->name('academics.classes.store');
    Route::get('classes/edit/{id}', [ClassController::class, 'edit'])->name('academics.classes.edit');
    Route::post('classes/edit/{id}', [ClassController::class, 'update'])->name('academics.classes.update');
    Route::get('classes/delete/{id}', [ClassController::class, 'destroy'])->name('academics.classes.destroy');
    Route::get('classes/get_section/{id}', [ClassController::class, 'getSectionHtml'])->name('academics.classes.get_section');

    // Sections
    Route::get('sections', [SectionController::class, 'index'])->name('academics.sections.index');
    Route::post('sections', [SectionController::class, 'store'])->name('academics.sections.store');
    Route::get('sections/edit/{id}', [SectionController::class, 'edit'])->name('academics.sections.edit');
    Route::post('sections/edit/{id}', [SectionController::class, 'update'])->name('academics.sections.update');
    Route::get('sections/delete/{id}', [SectionController::class, 'destroy'])->name('academics.sections.destroy');
    Route::get('sections/getByClass', [SectionController::class, 'getByClass'])->name('academics.sections.getByClass');

    // Subjects
    Route::get('admin/subject', [SubjectController::class, 'index'])->name('academics.subjects.index');
    Route::post('admin/subject', [SubjectController::class, 'store'])->name('academics.subjects.store');
    Route::get('admin/subject/edit/{id}', [SubjectController::class, 'edit'])->name('academics.subjects.edit');
    Route::post('admin/subject/edit/{id}', [SubjectController::class, 'update'])->name('academics.subjects.update');
    Route::get('admin/subject/delete/{id}', [SubjectController::class, 'destroy'])->name('academics.subjects.destroy');

    // Subject groups
    Route::get('admin/subjectgroup', [SubjectGroupController::class, 'index'])->name('academics.subject_groups.index');
    Route::post('admin/subjectgroup', [SubjectGroupController::class, 'store'])->name('academics.subject_groups.store');
    Route::get('admin/subjectgroup/edit/{id}', [SubjectGroupController::class, 'edit'])->name('academics.subject_groups.edit');
    Route::post('admin/subjectgroup/edit/{id}', [SubjectGroupController::class, 'update'])->name('academics.subject_groups.update');
    Route::get('admin/subjectgroup/delete/{id}', [SubjectGroupController::class, 'destroy'])->name('academics.subject_groups.destroy');
    Route::post('admin/subjectgroup/getGroupByClassandSection', [SubjectGroupController::class, 'getGroupByClassAndSection'])->name('academics.subject_groups.getByClassSection');
    Route::post('admin/subjectgroup/getGroupsubjects', [SubjectGroupController::class, 'getGroupSubjects'])->name('academics.subject_groups.getGroupSubjects');
    Route::post('admin/subjectgroup/getAllSubjectByClassandSection', [SubjectGroupController::class, 'getAllSubjectByClassandSection'])->name('academics.subject_groups.getAllByClassSection');

    // School houses
    Route::get('admin/schoolhouse', [SchoolHouseController::class, 'index'])->name('academics.school_houses.index');
    Route::get('admin/schoolhouse/index', [SchoolHouseController::class, 'index']);
    Route::post('admin/schoolhouse/create', [SchoolHouseController::class, 'store'])->name('academics.school_houses.store');
    Route::get('admin/schoolhouse/edit/{id}', [SchoolHouseController::class, 'edit'])->name('academics.school_houses.edit');
    Route::post('admin/schoolhouse/edit/{id}', [SchoolHouseController::class, 'update'])->name('academics.school_houses.update');
    Route::get('admin/schoolhouse/delete/{id}', [SchoolHouseController::class, 'destroy'])->name('academics.school_houses.destroy');

    // Grades
    Route::get('admin/grade', [GradeController::class, 'index'])->name('academics.grades.index');
    Route::get('admin/grade/index', [GradeController::class, 'index']);
    Route::post('admin/grade', [GradeController::class, 'store'])->name('academics.grades.store');
    Route::post('admin/grade/index', [GradeController::class, 'store']);
    Route::get('admin/grade/edit/{id}', [GradeController::class, 'edit'])->name('academics.grades.edit');
    Route::post('admin/grade/edit/{id}', [GradeController::class, 'update'])->name('academics.grades.update');
    Route::get('admin/grade/delete/{id}', [GradeController::class, 'destroy'])->name('academics.grades.destroy');

    // Mark divisions
    Route::get('admin/marksdivision', [MarkDivisionController::class, 'index'])->name('academics.mark_divisions.index');
    Route::get('admin/marksdivision/index', [MarkDivisionController::class, 'index']);
    Route::post('admin/marksdivision', [MarkDivisionController::class, 'store'])->name('academics.mark_divisions.store');
    Route::post('admin/marksdivision/index', [MarkDivisionController::class, 'store']);
    Route::get('admin/marksdivision/edit/{id}', [MarkDivisionController::class, 'edit'])->name('academics.mark_divisions.edit');
    Route::post('admin/marksdivision/edit/{id}', [MarkDivisionController::class, 'update'])->name('academics.mark_divisions.update');
    Route::get('admin/marksdivision/delete/{id}', [MarkDivisionController::class, 'destroy'])->name('academics.mark_divisions.destroy');

    // Custom fields
    Route::get('admin/customfield', [CustomFieldController::class, 'index'])->name('academics.custom_fields.index');
    Route::get('admin/customfield/index', [CustomFieldController::class, 'index']);
    Route::post('admin/customfield', [CustomFieldController::class, 'store'])->name('academics.custom_fields.store');
    Route::post('admin/customfield/index', [CustomFieldController::class, 'store']);
    Route::get('admin/customfield/edit/{id}', [CustomFieldController::class, 'edit'])->name('academics.custom_fields.edit');
    Route::post('admin/customfield/edit/{id}', [CustomFieldController::class, 'update'])->name('academics.custom_fields.update');
    Route::get('admin/customfield/delete/{id}', [CustomFieldController::class, 'destroy'])->name('academics.custom_fields.destroy');
    Route::post('admin/customfield/updateorder', [CustomFieldController::class, 'updateOrder'])->name('academics.custom_fields.update_order');
});
