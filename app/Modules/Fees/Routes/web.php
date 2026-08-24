<?php

use App\Modules\Fees\Controllers\FeeAssignController;
use App\Modules\Fees\Controllers\FeeDiscountController;
use App\Modules\Fees\Controllers\FeeGroupController;
use App\Modules\Fees\Controllers\FeeMasterController;
use App\Modules\Fees\Controllers\FeeTypeController;
use App\Modules\Fees\Controllers\FeesForwardController;
use App\Modules\Fees\Controllers\ModuleStatusController;
use App\Modules\Fees\Controllers\OfflinePaymentController;
use App\Modules\Fees\Controllers\StudentFeeController;
use App\Modules\Fees\Controllers\UserFeesController;
use App\Modules\Fees\Controllers\UserOfflinePaymentController;
use Illuminate\Support\Facades\Route;

Route::get('migration-status/fees', [ModuleStatusController::class, 'status'])->name('fees.migration_status');

Route::middleware(['staff.auth'])->group(function () {
    // Fee types (CI admin/feetype)
    Route::get('admin/feetype', [FeeTypeController::class, 'index'])->name('fees.fee_types.index');
    Route::get('admin/feetype/index', [FeeTypeController::class, 'index']);
    Route::post('admin/feetype', [FeeTypeController::class, 'store'])->name('fees.fee_types.store');
    Route::post('admin/feetype/index', [FeeTypeController::class, 'store']);
    Route::get('admin/feetype/edit/{id}', [FeeTypeController::class, 'edit'])->name('fees.fee_types.edit');
    Route::post('admin/feetype/edit/{id}', [FeeTypeController::class, 'update'])->name('fees.fee_types.update');
    Route::get('admin/feetype/delete/{id}', [FeeTypeController::class, 'destroy'])->name('fees.fee_types.destroy');

    // Fee groups (CI admin/feegroup)
    Route::get('admin/feegroup', [FeeGroupController::class, 'index'])->name('fees.fee_groups.index');
    Route::get('admin/feegroup/index', [FeeGroupController::class, 'index']);
    Route::post('admin/feegroup', [FeeGroupController::class, 'store'])->name('fees.fee_groups.store');
    Route::post('admin/feegroup/index', [FeeGroupController::class, 'store']);
    Route::get('admin/feegroup/edit/{id}', [FeeGroupController::class, 'edit'])->name('fees.fee_groups.edit');
    Route::post('admin/feegroup/edit/{id}', [FeeGroupController::class, 'update'])->name('fees.fee_groups.update');
    Route::get('admin/feegroup/delete/{id}', [FeeGroupController::class, 'destroy'])->name('fees.fee_groups.destroy');

    // Fee master (CI admin/feemaster)
    Route::get('admin/feemaster', [FeeMasterController::class, 'index'])->name('fees.fee_masters.index');
    Route::get('admin/feemaster/index', [FeeMasterController::class, 'index']);
    Route::post('admin/feemaster', [FeeMasterController::class, 'store'])->name('fees.fee_masters.store');
    Route::post('admin/feemaster/index', [FeeMasterController::class, 'store']);
    Route::get('admin/feemaster/edit/{id}', [FeeMasterController::class, 'edit'])->name('fees.fee_masters.edit');
    Route::post('admin/feemaster/edit/{id}', [FeeMasterController::class, 'update'])->name('fees.fee_masters.update');
    Route::get('admin/feemaster/delete/{id}', [FeeMasterController::class, 'destroy'])->name('fees.fee_masters.destroy');
    Route::get('admin/feemaster/deletegrp/{id}', [FeeMasterController::class, 'destroyGroup'])->name('fees.fee_masters.destroy_group');
    Route::post('admin/feemaster/remove_row', [FeeMasterController::class, 'removeRow'])->name('fees.fee_masters.remove_row');

    // Assign fees group to students (CI feemaster/assign + studentfee/addfeegroup)
    Route::match(['get', 'post'], 'admin/feemaster/assign/{id}', [FeeAssignController::class, 'assign'])->name('fees.fee_masters.assign');
    Route::post('studentfee/addfeegroup', [FeeAssignController::class, 'save'])->name('fees.fee_masters.assign_save');

    // Fees discounts (CI admin/feediscount)
    Route::get('admin/feediscount', [FeeDiscountController::class, 'index'])->name('fees.fee_discounts.index');
    Route::get('admin/feediscount/index', [FeeDiscountController::class, 'index']);
    Route::post('admin/feediscount', [FeeDiscountController::class, 'store'])->name('fees.fee_discounts.store');
    Route::post('admin/feediscount/index', [FeeDiscountController::class, 'store']);
    Route::get('admin/feediscount/edit/{id}', [FeeDiscountController::class, 'edit'])->name('fees.fee_discounts.edit');
    Route::post('admin/feediscount/edit/{id}', [FeeDiscountController::class, 'update'])->name('fees.fee_discounts.update');
    Route::get('admin/feediscount/delete/{id}', [FeeDiscountController::class, 'destroy'])->name('fees.fee_discounts.destroy');
    Route::match(['get', 'post'], 'admin/feediscount/assign/{id}', [FeeDiscountController::class, 'assign'])->name('fees.fee_discounts.assign');
    Route::post('admin/feediscount/studentdiscount', [FeeDiscountController::class, 'saveAssign'])->name('fees.fee_discounts.assign_save');

    // Collect fees (CI studentfee/*) — Slice 5 core
    Route::match(['get', 'post'], 'studentfee', [StudentFeeController::class, 'index'])->name('fees.studentfee.index');
    Route::match(['get', 'post'], 'studentfee/index', [StudentFeeController::class, 'index']);
    Route::get('studentfee/addfee/{id}', [StudentFeeController::class, 'addfee'])->name('fees.studentfee.addfee');
    Route::get('studentfee/collect', [StudentFeeController::class, 'collectForm'])->name('fees.studentfee.collect');
    Route::post('studentfee/addstudentfee', [StudentFeeController::class, 'addstudentfee'])->name('fees.studentfee.addstudentfee');
    Route::post('studentfee/getcollectfee', [StudentFeeController::class, 'collectGroupForm'])->name('fees.studentfee.collect_group');
    Route::post('studentfee/addfeegrp', [StudentFeeController::class, 'addfeegrp'])->name('fees.studentfee.addfeegrp');
    Route::post('studentfee/deleteFee', [StudentFeeController::class, 'deleteFee'])->name('fees.studentfee.deleteFee');
    Route::match(['get', 'post'], 'studentfee/searchpayment', [StudentFeeController::class, 'searchpayment'])->name('fees.studentfee.searchpayment');
    Route::match(['get', 'post'], 'studentfee/feesearch', [StudentFeeController::class, 'feesearch'])->name('fees.studentfee.feesearch');
    Route::post('studentfee/printFeesByName', [StudentFeeController::class, 'printFeesByName'])->name('fees.studentfee.printFeesByName');
    Route::get('studentfee/printFeesByName', [StudentFeeController::class, 'printFeesByNamePage'])->name('fees.studentfee.printFeesByName.page');
    Route::post('studentfee/printFeesByGroup', [StudentFeeController::class, 'printFeesByGroup'])->name('fees.studentfee.printFeesByGroup');
    Route::get('studentfee/printFeesByGroup', [StudentFeeController::class, 'printFeesByGroupPage'])->name('fees.studentfee.printFeesByGroup.page');
    Route::post('studentfee/printFeesByGroupArray', [StudentFeeController::class, 'printFeesByGroupArray'])->name('fees.studentfee.printFeesByGroupArray');

    // Fees carry forward (CI admin/feesforward)
    Route::match(['get', 'post'], 'admin/feesforward', [FeesForwardController::class, 'index'])->name('fees.feesforward.index');
    Route::match(['get', 'post'], 'admin/feesforward/index', [FeesForwardController::class, 'index']);

    // Offline bank payments (CI admin/offlinepayment)
    Route::get('admin/offlinepayment', [OfflinePaymentController::class, 'index'])->name('fees.offlinepayment.index');
    Route::get('admin/offlinepayment/index', [OfflinePaymentController::class, 'index']);
    Route::get('admin/offlinepayment/view/{id}', [OfflinePaymentController::class, 'show'])->name('fees.offlinepayment.show');
    Route::post('admin/offlinepayment/update', [OfflinePaymentController::class, 'update'])->name('fees.offlinepayment.update');
    Route::get('admin/offlinepayment/download/{id}', [OfflinePaymentController::class, 'download'])->name('fees.offlinepayment.download');
});

Route::middleware([
    'student_parent.auth',
    'student_parent.login_token',
    'student_parent.selected_class',
    'student_parent.permission:fees',
])->group(function () {
    // CI user/user/getfees
    Route::get('user/user/getfees', [UserFeesController::class, 'getfees'])->name('user.fees.getfees');
    Route::get('user/getfees', [UserFeesController::class, 'getfees']);
    Route::post('user/user/printFeesByName', [UserFeesController::class, 'printFeesByName'])->name('user.fees.printFeesByName');
    Route::get('user/user/printFeesByName', [UserFeesController::class, 'printFeesByNamePage'])->name('user.fees.printFeesByName.page');
    Route::post('user/user/printFeesByGroupArray', [UserFeesController::class, 'printFeesByGroupArray'])->name('user.fees.printFeesByGroupArray');
    Route::post('user/user/getProcessingfees', [UserFeesController::class, 'getProcessingfees'])->name('user.fees.getProcessingfees');

    // CI user/gateway/Payment::pay offline_payment → user/offlinepayment
    Route::post('user/offlinepayment/start', [UserOfflinePaymentController::class, 'start'])
        ->name('user.offlinepayment.start');
    Route::match(['get', 'post'], 'user/offlinepayment', [UserOfflinePaymentController::class, 'index'])
        ->name('user.offlinepayment.index');
    Route::match(['get', 'post'], 'user/offlinepayment/index', [UserOfflinePaymentController::class, 'index']);
    Route::get('user/offlinepayment/requests', [UserOfflinePaymentController::class, 'requests'])
        ->name('user.offlinepayment.requests');
    Route::get('user/offlinepayment/view/{id}', [UserOfflinePaymentController::class, 'show'])
        ->whereNumber('id')
        ->name('user.offlinepayment.show');
    Route::get('user/offlinepayment/download/{id}', [UserOfflinePaymentController::class, 'download'])
        ->whereNumber('id')
        ->name('user.offlinepayment.download');
});
