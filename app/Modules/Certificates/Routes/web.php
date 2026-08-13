<?php

use App\Modules\Certificates\Controllers\CertificateTemplateController;
use App\Modules\Certificates\Controllers\GenerateCertificateController;
use App\Modules\Certificates\Controllers\GenerateStudentIdCardController;
use App\Modules\Certificates\Controllers\ModuleStatusController;
use App\Modules\Certificates\Controllers\StudentIdCardTemplateController;
use Illuminate\Support\Facades\Route;

Route::get('migration-status/certificates', [ModuleStatusController::class, 'status'])->name('certificates.migration_status');

Route::middleware(['staff.auth'])->group(function () {
    // Student certificate templates (CI admin/certificate)
    Route::get('admin/certificate', [CertificateTemplateController::class, 'index'])->name('certificates.templates.index');
    Route::get('admin/certificate/index', [CertificateTemplateController::class, 'index']);
    Route::post('admin/certificate', [CertificateTemplateController::class, 'store'])->name('certificates.templates.store');
    Route::get('admin/certificate/edit/{id}', [CertificateTemplateController::class, 'edit'])->name('certificates.templates.edit');
    Route::post('admin/certificate/edit/{id}', [CertificateTemplateController::class, 'update'])->name('certificates.templates.update');
    Route::get('admin/certificate/delete/{id}', [CertificateTemplateController::class, 'destroy'])->name('certificates.templates.destroy');
    Route::get('admin/certificate/preview/{id}', [CertificateTemplateController::class, 'preview'])->name('certificates.templates.preview');

    // Generate student certificates (CI admin/generatecertificate)
    Route::match(['get', 'post'], 'admin/generatecertificate', [GenerateCertificateController::class, 'index'])
        ->name('certificates.generate.index');
    Route::match(['get', 'post'], 'admin/generatecertificate/search', [GenerateCertificateController::class, 'index'])
        ->name('certificates.generate.search');
    Route::post('admin/generatecertificate/print', [GenerateCertificateController::class, 'print'])
        ->name('certificates.generate.print');

    // Student ID card templates (CI admin/studentidcard)
    Route::get('admin/studentidcard', [StudentIdCardTemplateController::class, 'index'])
        ->name('certificates.idcard_templates.index');
    Route::get('admin/studentidcard/index', [StudentIdCardTemplateController::class, 'index']);
    Route::post('admin/studentidcard/create', [StudentIdCardTemplateController::class, 'store'])
        ->name('certificates.idcard_templates.store');
    Route::get('admin/studentidcard/edit/{id}', [StudentIdCardTemplateController::class, 'edit'])
        ->name('certificates.idcard_templates.edit');
    Route::post('admin/studentidcard/edit/{id}', [StudentIdCardTemplateController::class, 'update'])
        ->name('certificates.idcard_templates.update');
    Route::get('admin/studentidcard/delete/{id}', [StudentIdCardTemplateController::class, 'destroy'])
        ->name('certificates.idcard_templates.destroy');
    Route::get('admin/studentidcard/preview/{id}', [StudentIdCardTemplateController::class, 'preview'])
        ->name('certificates.idcard_templates.preview');

    // Generate student ID cards (CI admin/generateidcard)
    Route::match(['get', 'post'], 'admin/generateidcard', [GenerateStudentIdCardController::class, 'index'])
        ->name('certificates.idcard_generate.index');
    Route::match(['get', 'post'], 'admin/generateidcard/search', [GenerateStudentIdCardController::class, 'index'])
        ->name('certificates.idcard_generate.search');
    Route::post('admin/generateidcard/print', [GenerateStudentIdCardController::class, 'print'])
        ->name('certificates.idcard_generate.print');
});
