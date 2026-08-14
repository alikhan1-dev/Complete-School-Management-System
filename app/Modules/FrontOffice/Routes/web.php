<?php

use App\Modules\FrontOffice\Controllers\ComplaintController;
use App\Modules\FrontOffice\Controllers\ComplaintTypeController;
use App\Modules\FrontOffice\Controllers\DispatchController;
use App\Modules\FrontOffice\Controllers\EnquiryController;
use App\Modules\FrontOffice\Controllers\GeneralCallController;
use App\Modules\FrontOffice\Controllers\ReceiveController;
use App\Modules\FrontOffice\Controllers\ReferenceController;
use App\Modules\FrontOffice\Controllers\SourceController;
use App\Modules\FrontOffice\Controllers\ModuleStatusController;
use App\Modules\FrontOffice\Controllers\StudentVisitorController;
use App\Modules\FrontOffice\Controllers\VisitorsController;
use App\Modules\FrontOffice\Controllers\VisitorsPurposeController;
use Illuminate\Support\Facades\Route;

Route::get('migration-status/frontoffice', [ModuleStatusController::class, 'status'])->name('frontoffice.migration_status');

Route::middleware([
    'student_parent.auth',
    'student_parent.login_token',
    'student_parent.selected_class',
    'student_parent.permission:visitor_book',
])->group(function () {
    Route::get('user/visitors', [StudentVisitorController::class, 'index'])->name('user.visitors.index');
    Route::get('user/visitors/index', [StudentVisitorController::class, 'index']);
    Route::get('user/visitors/download/{id}', [StudentVisitorController::class, 'download'])
        ->whereNumber('id')
        ->name('user.visitors.download');
});

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

    Route::get('admin/visitors', [VisitorsController::class, 'index'])->name('frontoffice.visitors.index');
    Route::get('admin/visitors/index', [VisitorsController::class, 'index']);
    Route::post('admin/visitors/add', [VisitorsController::class, 'add'])->name('frontoffice.visitors.add');
    Route::post('admin/visitors/editvisitor', [VisitorsController::class, 'editvisitor'])->name('frontoffice.visitors.editvisitor');
    Route::post('admin/visitors/edit', [VisitorsController::class, 'edit'])->name('frontoffice.visitors.edit');
    Route::post('admin/visitors/delete', [VisitorsController::class, 'delete'])->name('frontoffice.visitors.delete');
    Route::get('admin/visitors/details/{id}', [VisitorsController::class, 'details'])
        ->whereNumber('id')
        ->name('frontoffice.visitors.details');
    Route::get('admin/visitors/download/{id}', [VisitorsController::class, 'download'])
        ->whereNumber('id')
        ->name('frontoffice.visitors.download');
    Route::post('admin/visitors/getstudent', [VisitorsController::class, 'getstudent'])->name('frontoffice.visitors.getstudent');
    Route::get('admin/visitors/staffvisitor', [VisitorsController::class, 'staffvisitor'])->name('frontoffice.visitors.staff');

    Route::match(['get', 'post'], 'admin/complaint', [ComplaintController::class, 'index'])->name('frontoffice.complaint.index');
    Route::get('admin/complaint/index', [ComplaintController::class, 'index']);
    Route::match(['get', 'post'], 'admin/complaint/edit/{id}', [ComplaintController::class, 'edit'])
        ->whereNumber('id')
        ->name('frontoffice.complaint.edit');
    Route::get('admin/complaint/details/{id}', [ComplaintController::class, 'details'])
        ->whereNumber('id')
        ->name('frontoffice.complaint.details');
    Route::get('admin/complaint/delete/{id}', [ComplaintController::class, 'delete'])
        ->whereNumber('id')
        ->name('frontoffice.complaint.delete');
    Route::get('admin/complaint/download/{id}', [ComplaintController::class, 'download'])
        ->whereNumber('id')
        ->name('frontoffice.complaint.download');

    Route::match(['get', 'post'], 'admin/dispatch', [DispatchController::class, 'index'])->name('frontoffice.dispatch.index');
    Route::get('admin/dispatch/index', [DispatchController::class, 'index']);
    Route::match(['get', 'post'], 'admin/dispatch/editdispatch/{id}', [DispatchController::class, 'editdispatch'])
        ->whereNumber('id')
        ->name('frontoffice.dispatch.edit');
    Route::get('admin/dispatch/details/{id}/{type}', [DispatchController::class, 'details'])
        ->whereNumber('id')
        ->whereIn('type', ['dispatch', 'receive'])
        ->name('frontoffice.dispatch.details');
    Route::get('admin/dispatch/delete/{id}', [DispatchController::class, 'delete'])
        ->whereNumber('id')
        ->name('frontoffice.dispatch.delete');
    Route::get('admin/dispatch/download/{id}', [DispatchController::class, 'download'])
        ->whereNumber('id')
        ->name('frontoffice.dispatch.download');

    Route::match(['get', 'post'], 'admin/receive', [ReceiveController::class, 'index'])->name('frontoffice.receive.index');
    Route::get('admin/receive/index', [ReceiveController::class, 'index']);
    Route::match(['get', 'post'], 'admin/receive/editreceive/{id}', [ReceiveController::class, 'editreceive'])
        ->whereNumber('id')
        ->name('frontoffice.receive.edit');
    Route::get('admin/receive/delete/{id}', [ReceiveController::class, 'delete'])
        ->whereNumber('id')
        ->name('frontoffice.receive.delete');
    Route::get('admin/receive/download/{id}', [ReceiveController::class, 'download'])
        ->whereNumber('id')
        ->name('frontoffice.receive.download');

    Route::match(['get', 'post'], 'admin/generalcall', [GeneralCallController::class, 'index'])->name('frontoffice.generalcall.index');
    Route::get('admin/generalcall/index', [GeneralCallController::class, 'index']);
    Route::match(['get', 'post'], 'admin/generalcall/edit/{id}', [GeneralCallController::class, 'edit'])
        ->whereNumber('id')
        ->name('frontoffice.generalcall.edit');
    Route::get('admin/generalcall/details/{id}', [GeneralCallController::class, 'details'])
        ->whereNumber('id')
        ->name('frontoffice.generalcall.details');
    Route::get('admin/generalcall/delete/{id}', [GeneralCallController::class, 'delete'])
        ->whereNumber('id')
        ->name('frontoffice.generalcall.delete');
    Route::match(['get', 'post'], 'admin/generalcall/getcalllist', [GeneralCallController::class, 'getcalllist'])
        ->name('frontoffice.generalcall.getcalllist');

    Route::match(['get', 'post'], 'admin/visitorspurpose', [VisitorsPurposeController::class, 'index'])->name('frontoffice.setup.purpose.index');
    Route::get('admin/visitorspurpose/index', [VisitorsPurposeController::class, 'index']);
    Route::match(['get', 'post'], 'admin/visitorspurpose/edit/{id}', [VisitorsPurposeController::class, 'edit'])
        ->whereNumber('id')
        ->name('frontoffice.setup.purpose.edit');
    Route::get('admin/visitorspurpose/delete/{id}', [VisitorsPurposeController::class, 'delete'])
        ->whereNumber('id')
        ->name('frontoffice.setup.purpose.delete');

    Route::match(['get', 'post'], 'admin/complainttype', [ComplaintTypeController::class, 'index'])->name('frontoffice.setup.complainttype.index');
    Route::get('admin/complainttype/index', [ComplaintTypeController::class, 'index']);
    Route::match(['get', 'post'], 'admin/complainttype/editcomplainttype/{id}', [ComplaintTypeController::class, 'editcomplainttype'])
        ->whereNumber('id')
        ->name('frontoffice.setup.complainttype.edit');
    Route::get('admin/complainttype/delete/{id}', [ComplaintTypeController::class, 'delete'])
        ->whereNumber('id')
        ->name('frontoffice.setup.complainttype.delete');

    Route::match(['get', 'post'], 'admin/source', [SourceController::class, 'index'])->name('frontoffice.setup.source.index');
    Route::get('admin/source/index', [SourceController::class, 'index']);
    Route::match(['get', 'post'], 'admin/source/edit/{id}', [SourceController::class, 'edit'])
        ->whereNumber('id')
        ->name('frontoffice.setup.source.edit');
    Route::get('admin/source/delete/{id}', [SourceController::class, 'delete'])
        ->whereNumber('id')
        ->name('frontoffice.setup.source.delete');

    Route::match(['get', 'post'], 'admin/reference', [ReferenceController::class, 'index'])->name('frontoffice.setup.reference.index');
    Route::get('admin/reference/index', [ReferenceController::class, 'index']);
    Route::match(['get', 'post'], 'admin/reference/edit/{id}', [ReferenceController::class, 'edit'])
        ->whereNumber('id')
        ->name('frontoffice.setup.reference.edit');
    Route::get('admin/reference/delete/{id}', [ReferenceController::class, 'delete'])
        ->whereNumber('id')
        ->name('frontoffice.setup.reference.delete');
});
