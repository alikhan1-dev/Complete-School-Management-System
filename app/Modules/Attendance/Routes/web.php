<?php

use App\Modules\Attendance\Controllers\ModuleStatusController;
use App\Modules\Attendance\Controllers\StaffAttendanceController;
use App\Modules\Attendance\Controllers\StuAttendenceController;
use App\Modules\Attendance\Controllers\SubjectAttendenceController;
use Illuminate\Support\Facades\Route;

Route::get('migration-status/attendance', [ModuleStatusController::class, 'status'])->name('attendance.migration_status');

Route::middleware(['staff.auth'])->group(function () {
    // CI admin/stuattendence — student day attendance
    Route::match(['get', 'post'], 'admin/stuattendence', [StuAttendenceController::class, 'index'])->name('attendance.stuattendence.index');
    Route::match(['get', 'post'], 'admin/stuattendence/index', [StuAttendenceController::class, 'index']);

    // CI admin/stuattendence/attendencereport — Attendance By Date (read-only prepared list)
    Route::match(['get', 'post'], 'admin/stuattendence/attendencereport', [StuAttendenceController::class, 'attendencereport'])
        ->name('attendance.stuattendence.attendencereport');

    // CI admin/subjectattendence — period / subject attendance
    Route::match(['get', 'post'], 'admin/subjectattendence', [SubjectAttendenceController::class, 'index'])
        ->name('attendance.subjectattendence.index');
    Route::match(['get', 'post'], 'admin/subjectattendence/index', [SubjectAttendenceController::class, 'index']);

    // CI admin/subjectattendence/reportbydate — period attendance matrix by date
    Route::match(['get', 'post'], 'admin/subjectattendence/reportbydate', [SubjectAttendenceController::class, 'reportbydate'])
        ->name('attendance.subjectattendence.reportbydate');

    // CI admin/subjectgroup/getSubjectByClassandSectionDate — periods for weekday of date
    Route::post('admin/subjectgroup/getSubjectByClassandSectionDate', [SubjectAttendenceController::class, 'getSubjectByClassandSectionDate'])
        ->name('attendance.subjectattendence.periods_by_date');

    // CI admin/staffattendance — staff day attendance
    Route::match(['get', 'post'], 'admin/staffattendance', [StaffAttendanceController::class, 'index'])
        ->name('attendance.staffattendance.index');
    Route::match(['get', 'post'], 'admin/staffattendance/index', [StaffAttendanceController::class, 'index']);
});
