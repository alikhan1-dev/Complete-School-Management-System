<?php

use App\Modules\Reports\Controllers\ModuleStatusController;
use App\Modules\Reports\Controllers\StudentInformationReportController;
use Illuminate\Support\Facades\Route;

Route::get('migration-status/reports', [ModuleStatusController::class, 'status'])->name('reports.migration_status');

Route::middleware(['staff.auth'])->group(function () {
    Route::get('report/studentinformation', [StudentInformationReportController::class, 'studentinformation'])
        ->name('reports.student_information');
    Route::match(['get', 'post'], 'report/studentreport', [StudentInformationReportController::class, 'studentreport'])
        ->name('reports.student_report');
    Route::post('report/studentreportvalidation', [StudentInformationReportController::class, 'studentreportvalidation'])
        ->name('reports.student_report.validation');
    Route::match(['get', 'post'], 'report/dtstudentreportlist', [StudentInformationReportController::class, 'dtstudentreportlist'])
        ->name('reports.student_report.datatable');
    Route::get('report/classsectionreport', [StudentInformationReportController::class, 'classsectionreport'])
        ->name('reports.class_section');
    Route::get('report/boys_girls_ratio', [StudentInformationReportController::class, 'boys_girls_ratio'])
        ->name('reports.gender_ratio');
    Route::get('report/student_teacher_ratio', [StudentInformationReportController::class, 'student_teacher_ratio'])
        ->name('reports.teacher_ratio');

    Route::match(['get', 'post'], 'report/guardianreport', [StudentInformationReportController::class, 'guardianreport'])
        ->name('reports.guardian');
    Route::get('report/admissionreport', [StudentInformationReportController::class, 'admissionreport'])
        ->name('reports.history');
    Route::post('report/admissionreport', [StudentInformationReportController::class, 'admissionreportSearch']);
    Route::post('report/admissionsearchvalidation', [StudentInformationReportController::class, 'admissionsearchvalidation'])
        ->name('reports.history.validation');
    Route::match(['get', 'post'], 'report/dtadmissionreportlist', [StudentInformationReportController::class, 'dtadmissionreportlist'])
        ->name('reports.history.datatable');
    Route::match(['get', 'post'], 'report/logindetailreport', [StudentInformationReportController::class, 'logindetailreport'])
        ->name('reports.student_login');
    Route::match(['get', 'post'], 'report/parentlogindetailreport', [StudentInformationReportController::class, 'parentlogindetailreport'])
        ->name('reports.parent_login');
    Route::post('report/searchloginvalidation', [StudentInformationReportController::class, 'searchloginvalidation'])
        ->name('reports.login.validation');
    Route::match(['get', 'post'], 'report/dtcredentialreportlist', [StudentInformationReportController::class, 'dtcredentialreportlist'])
        ->name('reports.student_login.datatable');
    Route::match(['get', 'post'], 'report/dtparentcredentialreportlist', [StudentInformationReportController::class, 'dtparentcredentialreportlist'])
        ->name('reports.parent_login.datatable');
});