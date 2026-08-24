<?php

use App\Modules\Timetable\Controllers\ModuleStatusController;
use App\Modules\Timetable\Controllers\TimetableController;
use Illuminate\Support\Facades\Route;

Route::get('migration-status/timetable', [ModuleStatusController::class, 'status'])->name('timetable.migration_status');

Route::middleware(['staff.auth'])->group(function () {
    // CI admin/timetable/classreport — weekly class view (primary entry)
    Route::match(['get', 'post'], 'admin/timetable/classreport', [TimetableController::class, 'classreport'])
        ->name('timetable.classreport');
    Route::match(['get', 'post'], 'admin/timetable', [TimetableController::class, 'classreport'])
        ->name('timetable.index');

    // CI admin/timetable/create — edit periods by class/section/subject group
    Route::match(['get', 'post'], 'admin/timetable/create', [TimetableController::class, 'create'])
        ->name('timetable.create');

    // CI admin/timetable/savegroup — save one day (form POST)
    Route::post('admin/timetable/saveday', [TimetableController::class, 'saveDay'])
        ->name('timetable.save_day');

    // CI admin/timetable/mytimetable — teacher weekly timetable
    Route::get('admin/timetable/mytimetable', [TimetableController::class, 'mytimetable'])
        ->name('timetable.mytimetable');

    // CI admin/timetable/getteachertimetable — admin AJAX teacher picker
    Route::post('admin/timetable/getteachertimetable', [TimetableController::class, 'getTeacherTimetable'])
        ->name('timetable.get_teacher_timetable');

    // CI admin/timetable/printclasstimetable — class print HTML (JSON page)
    Route::post('admin/timetable/printclasstimetable', [TimetableController::class, 'printClassTimetable'])
        ->name('timetable.print_class');

    // CI admin/timetable/printteachertimetable — teacher print HTML (JSON page)
    Route::post('admin/timetable/printteachertimetable', [TimetableController::class, 'printTeacherTimetable'])
        ->name('timetable.print_teacher');
});
