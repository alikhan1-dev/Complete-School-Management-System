<?php

use App\Modules\Students\Controllers\AlumniController;
use App\Modules\Students\Controllers\AlumniEventController;
use App\Modules\Students\Controllers\DisableReasonController;
use App\Modules\Students\Controllers\DisabledStudentController;
use App\Modules\Students\Controllers\ModuleStatusController;
use App\Modules\Students\Controllers\StdTransferController;
use App\Modules\Students\Controllers\StudentController;
use App\Modules\Students\Controllers\StudentTimelineController;
use Illuminate\Support\Facades\Route;

Route::get('migration-status/students', [ModuleStatusController::class, 'status'])->name('students.migration_status');

Route::middleware(['staff.auth'])->group(function () {
    // CI admin/disable_reason
    Route::match(['get', 'post'], 'admin/disable_reason', [DisableReasonController::class, 'index'])
        ->name('students.disable_reasons.index');
    Route::match(['get', 'post'], 'admin/disable_reason/edit/{id}', [DisableReasonController::class, 'edit'])
        ->whereNumber('id')
        ->name('students.disable_reasons.edit');
    Route::get('admin/disable_reason/delete/{id}', [DisableReasonController::class, 'destroy'])
        ->whereNumber('id')
        ->name('students.disable_reasons.destroy');

    // CI admin/alumni alumnilist + add/delete (events deferred)
    Route::match(['get', 'post'], 'admin/alumni/alumnilist', [AlumniController::class, 'alumnilist'])
        ->name('students.alumni.list');
    Route::match(['get', 'post'], 'admin/alumni/add/{studentId}', [AlumniController::class, 'form'])
        ->whereNumber('studentId')
        ->name('students.alumni.form');
    Route::get('admin/alumni/deletestudent/{id}', [AlumniController::class, 'deletestudent'])
        ->whereNumber('id')
        ->name('students.alumni.delete');

    // CI admin/alumni events (mail/SMS + calendar deferred)
    Route::get('admin/alumni/events', [AlumniEventController::class, 'index'])
        ->name('students.alumni.events');
    Route::match(['get', 'post'], 'admin/alumni/event/create', [AlumniEventController::class, 'create'])
        ->name('students.alumni.event.create');
    Route::match(['get', 'post'], 'admin/alumni/event/edit/{id}', [AlumniEventController::class, 'edit'])
        ->whereNumber('id')
        ->name('students.alumni.event.edit');
    Route::get('admin/alumni/delete_event/{id}', [AlumniEventController::class, 'destroy'])
        ->whereNumber('id')
        ->name('students.alumni.event.delete');

    // CI student/disablestudentslist
    Route::match(['get', 'post'], 'student/disablestudentslist', [DisabledStudentController::class, 'index'])
        ->name('students.disabled.list');

    Route::get('student/search', [StudentController::class, 'search'])->name('students.search');
    Route::post('student/searchvalidation', [StudentController::class, 'searchValidation'])->name('students.search_validation');
    Route::post('student/dtstudentlist', [StudentController::class, 'datatable'])->name('students.datatable');
    Route::get('student/getByClassAndSection', [StudentController::class, 'getByClassAndSection'])->name('students.by_class_section');
    Route::get('student/getStudentRecordByID', [StudentController::class, 'getStudentRecordByID'])->name('students.record_by_id');

    Route::get('student/create', [StudentController::class, 'create'])->name('students.create');
    Route::post('student/create', [StudentController::class, 'store'])->name('students.store');

    Route::get('student/view/{id}', [StudentController::class, 'show'])->name('students.view');
    Route::get('student/edit/{id}', [StudentController::class, 'edit'])->name('students.edit');
    Route::post('student/edit/{id}', [StudentController::class, 'update'])->name('students.update');
    Route::get('student/delete/{id}', [StudentController::class, 'destroy'])->name('students.destroy');

    Route::post('student/create_doc', [StudentController::class, 'createDoc'])->name('students.create_doc');
    Route::get('student/download/{student_id}/{doc_id}', [StudentController::class, 'downloadDoc'])->name('students.download_doc');
    Route::get('student/doc_delete/{id}/{student_id}', [StudentController::class, 'destroyDoc'])->name('students.doc_delete');

    // Student timeline (CI admin/Timeline)
    Route::post('admin/timeline/add', [StudentTimelineController::class, 'store'])->name('students.timeline.store');
    Route::post('admin/timeline/editstudenttimeline', [StudentTimelineController::class, 'update'])->name('students.timeline.update');
    Route::post('admin/timeline/delete_timeline', [StudentTimelineController::class, 'destroy'])->name('students.timeline.destroy');
    Route::get('admin/timeline/download/{timeline_id}', [StudentTimelineController::class, 'download'])->name('students.timeline.download');

    Route::get('student/disablestudent/{id}', [StudentController::class, 'disable'])->name('students.disable');
    Route::post('student/enablestudent/{id}', [StudentController::class, 'enable'])->name('students.enable');
    Route::post('student/disable_reason', [StudentController::class, 'disableReason'])->name('students.disable_reason');
    Route::post('student/getlogindetail', [StudentController::class, 'getlogindetail'])->name('students.login_detail');
    Route::post('student/sendpassword', [StudentController::class, 'sendpassword'])->name('students.send_password');
    Route::post('student/send_parent_password', [StudentController::class, 'sendParentPassword'])->name('students.send_parent_password');

    // Promote / transfer (CI Stdtransfer)
    Route::match(['get', 'post'], 'admin/stdtransfer/index', [StdTransferController::class, 'index'])->name('students.stdtransfer.index');
    Route::match(['get', 'post'], 'admin/stdtransfer', [StdTransferController::class, 'index']);
    Route::post('admin/stdtransfer/promote', [StdTransferController::class, 'promote'])->name('students.stdtransfer.promote');
});
