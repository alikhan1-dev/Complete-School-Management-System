<?php

use App\Modules\Fees\Controllers\FeeAssignController;
use App\Modules\Fees\Controllers\FeeDiscountController;
use App\Modules\Fees\Controllers\FeeGroupController;
use App\Modules\Fees\Controllers\FeeMasterController;
use App\Modules\Fees\Controllers\FeeTypeController;
use App\Modules\Fees\Controllers\ModuleStatusController;
use App\Modules\Fees\Controllers\StudentFeeController;
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
    Route::post('studentfee/deleteFee', [StudentFeeController::class, 'deleteFee'])->name('fees.studentfee.deleteFee');
    Route::match(['get', 'post'], 'studentfee/searchpayment', [StudentFeeController::class, 'searchpayment'])->name('fees.studentfee.searchpayment');
});
