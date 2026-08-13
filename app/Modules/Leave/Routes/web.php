<?php

use App\Modules\Leave\Controllers\LeaveReportController;
use App\Modules\Leave\Controllers\LeaveRequestController;
use App\Modules\Leave\Controllers\LeaveTypeController;
use App\Modules\Leave\Controllers\ModuleStatusController;
use App\Modules\Leave\Controllers\StaffApplyLeaveController;
use App\Modules\Leave\Controllers\StudentApproveLeaveController;
use Illuminate\Support\Facades\Route;

Route::get('migration-status/leave', [ModuleStatusController::class, 'status'])->name('leave.migration_status');

Route::middleware(['staff.auth'])->group(function () {
    // CI admin/leavetypes
    Route::get('admin/leavetypes', [LeaveTypeController::class, 'index'])->name('leave.types.index');
    Route::get('admin/leavetypes/index', [LeaveTypeController::class, 'index']);
    Route::post('admin/leavetypes/createleavetype', [LeaveTypeController::class, 'store'])->name('leave.types.store');
    Route::get('admin/leavetypes/leaveedit/{id}', [LeaveTypeController::class, 'edit'])->whereNumber('id')->name('leave.types.edit');
    Route::get('admin/leavetypes/leavedelete/{id}', [LeaveTypeController::class, 'destroy'])->whereNumber('id')->name('leave.types.destroy');

    // CI admin/leaverequest/leaverequest
    Route::get('admin/leaverequest/leaverequest', [LeaveRequestController::class, 'index'])->name('leave.requests.index');
    Route::get('admin/leaverequest', [LeaveRequestController::class, 'index']);

    Route::get('admin/leaverequest/add', [LeaveRequestController::class, 'create'])->name('leave.requests.create');
    Route::post('admin/leaverequest/addLeave', [LeaveRequestController::class, 'store'])->name('leave.requests.store');

    Route::get('admin/leaverequest/edit/{id}', [LeaveRequestController::class, 'edit'])->whereNumber('id')->name('leave.requests.edit');
    Route::post('admin/leaverequest/edit/{id}', [LeaveRequestController::class, 'update'])->whereNumber('id')->name('leave.requests.update');

    Route::get('admin/leaverequest/view/{id}', [LeaveRequestController::class, 'statusForm'])->whereNumber('id')->name('leave.requests.view');
    Route::post('admin/leaverequest/leaveStatus/{id}', [LeaveRequestController::class, 'updateStatus'])->whereNumber('id')->name('leave.requests.status');

    Route::get('admin/leaverequest/remove/{id}/{staff_id?}', [LeaveRequestController::class, 'destroy'])
        ->whereNumber('id')
        ->name('leave.requests.destroy');

    Route::get('admin/leaverequest/downloadleaverequestdoc/{staffId}/{id}', [LeaveRequestController::class, 'download'])
        ->whereNumber(['staffId', 'id'])
        ->name('leave.requests.download');

    Route::match(['get', 'post'], 'admin/leaverequest/countLeave/{id}', [LeaveRequestController::class, 'countLeave'])
        ->whereNumber('id')
        ->name('leave.requests.count_leave');

    // CI admin/staff/leaverequest — staff self-apply portal
    Route::get('admin/staff/leaverequest', [StaffApplyLeaveController::class, 'index'])->name('leave.staff_apply.index');
    Route::get('admin/staff/leaverequest/apply', [StaffApplyLeaveController::class, 'create'])->name('leave.staff_apply.create');
    Route::post('admin/leaverequest/add_staff_leave', [StaffApplyLeaveController::class, 'store'])->name('leave.staff_apply.store');
    Route::get('admin/staff/leaverequest/view/{id}', [StaffApplyLeaveController::class, 'view'])->whereNumber('id')->name('leave.staff_apply.view');
    Route::get('admin/staff/leaverequest/remove/{id}', [StaffApplyLeaveController::class, 'destroy'])->whereNumber('id')->name('leave.staff_apply.destroy');

    // CI admin/approve_leave — student leave
    Route::match(['get', 'post'], 'admin/approve_leave', [StudentApproveLeaveController::class, 'index'])->name('leave.student_approve.index');
    Route::get('admin/approve_leave/index', [StudentApproveLeaveController::class, 'index']);
    Route::get('admin/approve_leave/add', [StudentApproveLeaveController::class, 'create'])->name('leave.student_approve.create');
    Route::post('admin/approve_leave/add', [StudentApproveLeaveController::class, 'store'])->name('leave.student_approve.store');
    Route::get('admin/approve_leave/edit/{id}', [StudentApproveLeaveController::class, 'edit'])->whereNumber('id')->name('leave.student_approve.edit');
    Route::post('admin/approve_leave/edit/{id}', [StudentApproveLeaveController::class, 'update'])->whereNumber('id')->name('leave.student_approve.update');
    Route::post('admin/approve_leave/status/{id}', [StudentApproveLeaveController::class, 'updateStatus'])->whereNumber('id')->name('leave.student_approve.status');
    Route::get('admin/approve_leave/remove_leave/{id}', [StudentApproveLeaveController::class, 'destroy'])->whereNumber('id')->name('leave.student_approve.destroy');
    Route::get('admin/approve_leave/download/{id}', [StudentApproveLeaveController::class, 'download'])->whereNumber('id')->name('leave.student_approve.download');

    // CI report/leaverequestreport + myleaverequestreport
    Route::match(['get', 'post'], 'report/leaverequestreport', [LeaveReportController::class, 'leaveRequestReport'])
        ->name('leave.reports.request');
    Route::match(['get', 'post'], 'report/myleaverequestreport', [LeaveReportController::class, 'myLeaveRequestReport'])
        ->name('leave.reports.my_request');
});
