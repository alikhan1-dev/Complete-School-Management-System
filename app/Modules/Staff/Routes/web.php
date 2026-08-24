<?php

use App\Modules\Staff\Controllers\ModuleStatusController;
use App\Modules\Staff\Controllers\StaffController;
use App\Modules\Staff\Controllers\StaffTimelineController;
use Illuminate\Support\Facades\Route;

Route::get('migration-status/staff', [ModuleStatusController::class, 'status'])->name('staff.migration_status');

Route::middleware(['staff.auth'])->prefix('admin')->group(function () {
    Route::get('staff', [StaffController::class, 'index'])->middleware('permission:staff,can_view')->name('staff.index');
    Route::get('staff/datatable', [StaffController::class, 'datatable'])->middleware('permission:staff,can_view')->name('staff.datatable');
    Route::get('staff/create', [StaffController::class, 'create'])->middleware('permission:staff,can_add')->name('staff.create');
    Route::post('staff/create', [StaffController::class, 'store'])->middleware('permission:staff,can_add')->name('staff.store');
    Route::get('staff/edit/{id}', [StaffController::class, 'edit'])->middleware('permission:staff,can_edit')->name('staff.edit');
    Route::post('staff/edit/{id}', [StaffController::class, 'update'])->middleware('permission:staff,can_edit')->name('staff.update');
    Route::get('staff/profile/{id}', [StaffController::class, 'profile'])->name('staff.profile');
    Route::get('staff/download/{staff_id}/{doc}', [StaffController::class, 'downloadDocument'])->name('staff.download');
    Route::get('staff/doc_delete/{id}/{doc}', [StaffController::class, 'deleteDocument'])->middleware('permission:staff,can_edit')->name('staff.doc_delete');
    Route::post('staff/ajax_attendance', [StaffController::class, 'ajaxAttendance'])->name('staff.ajax_attendance');
    Route::post('staff/disablestaff/{id}', [StaffController::class, 'disableStaff'])->middleware('permission:disable_staff,can_view')->name('staff.disable');
    Route::get('staff/enablestaff/{id}', [StaffController::class, 'enableStaff'])->name('staff.enable');

    Route::post('timeline/add_staff_timeline', [StaffTimelineController::class, 'store'])->name('staff.timeline.store');
    Route::post('timeline/editstafftimeline', [StaffTimelineController::class, 'update'])->name('staff.timeline.update');
    Route::get('timeline/delete_staff_timeline/{id}', [StaffTimelineController::class, 'destroy'])->name('staff.timeline.destroy');
    Route::get('timeline/download_staff_timeline/{timelineId}', [StaffTimelineController::class, 'download'])->name('staff.timeline.download');
    Route::get('timeline/staff_timeline/{id}', [StaffTimelineController::class, 'listPartial'])->name('staff.timeline.list_partial');
});
