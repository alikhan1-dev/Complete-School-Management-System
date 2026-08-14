<?php

use App\Modules\FrontOffice\Controllers\EnquiryController;
use App\Modules\FrontOffice\Controllers\ModuleStatusController;
use Illuminate\Support\Facades\Route;

Route::get('migration-status/frontoffice', [ModuleStatusController::class, 'status'])->name('frontoffice.migration_status');

Route::middleware(['staff.auth'])->group(function () {
    Route::match(['get', 'post'], 'admin/enquiry', [EnquiryController::class, 'index'])->name('frontoffice.enquiry.index');
    Route::get('admin/enquiry/index', [EnquiryController::class, 'index']);
    Route::post('admin/enquiry/add', [EnquiryController::class, 'add'])->name('frontoffice.enquiry.add');
    Route::match(['get', 'post'], 'admin/enquiry/delete/{id}', [EnquiryController::class, 'delete'])
        ->whereNumber('id')
        ->name('frontoffice.enquiry.delete');
    Route::get('admin/enquiry/follow_up/{enquiry_id}/{status}/{created_by}', [EnquiryController::class, 'follow_up'])
        ->whereNumber('enquiry_id')
        ->whereNumber('created_by')
        ->name('frontoffice.enquiry.follow_up');
    Route::post('admin/enquiry/follow_up_insert', [EnquiryController::class, 'follow_up_insert'])
        ->name('frontoffice.enquiry.follow_up_insert');
    Route::get('admin/enquiry/follow_up_list/{id}', [EnquiryController::class, 'follow_up_list'])
        ->whereNumber('id')
        ->name('frontoffice.enquiry.follow_up_list');
    Route::get('admin/enquiry/details/{id}/{status}', [EnquiryController::class, 'details'])
        ->whereNumber('id')
        ->name('frontoffice.enquiry.details');
    Route::post('admin/enquiry/editpost/{id}', [EnquiryController::class, 'editpost'])
        ->whereNumber('id')
        ->name('frontoffice.enquiry.editpost');
    Route::match(['get', 'post'], 'admin/enquiry/follow_up_delete/{follow_up_id}/{enquiry_id}', [EnquiryController::class, 'follow_up_delete'])
        ->whereNumber('follow_up_id')
        ->whereNumber('enquiry_id')
        ->name('frontoffice.enquiry.follow_up_delete');
    Route::post('admin/enquiry/change_status', [EnquiryController::class, 'change_status'])
        ->name('frontoffice.enquiry.change_status');
    Route::post('admin/enquiry/check_number', [EnquiryController::class, 'check_number'])
        ->name('frontoffice.enquiry.check_number');
});
