<?php

use App\Modules\Payroll\Controllers\ModuleStatusController;
use App\Modules\Payroll\Controllers\PayrollController;
use App\Modules\Payroll\Controllers\PayrollReportController;
use Illuminate\Support\Facades\Route;

Route::get('migration-status/payroll', [ModuleStatusController::class, 'status'])->name('payroll.migration_status');

Route::middleware(['staff.auth'])->group(function () {
    // CI admin/payroll
    Route::match(['get', 'post'], 'admin/payroll', [PayrollController::class, 'index'])->name('payroll.index');
    Route::get('admin/payroll/index', [PayrollController::class, 'index']);

    Route::get('admin/payroll/search/{month}/{year}/{role?}', [PayrollController::class, 'search'])
        ->name('payroll.search')
        ->where(['month' => '[A-Za-z]+', 'year' => '[0-9]{4}']);

    Route::get('admin/payroll/create/{month}/{year}/{id}', [PayrollController::class, 'create'])
        ->whereNumber('id')
        ->where(['month' => '[A-Za-z]+', 'year' => '[0-9]{4}'])
        ->name('payroll.create');

    Route::post('admin/payroll/payslip', [PayrollController::class, 'payslip'])->name('payroll.payslip');

    Route::get('admin/payroll/edit/{id}', [PayrollController::class, 'edit'])->whereNumber('id')->name('payroll.edit');
    Route::post('admin/payroll/editpayroll', [PayrollController::class, 'editPayroll'])->name('payroll.editpayroll');

    Route::get('admin/payroll/pay/{staffId}/{month}/{year}', [PayrollController::class, 'payForm'])
        ->whereNumber('staffId')
        ->where(['month' => '[A-Za-z]+', 'year' => '[0-9]{4}'])
        ->name('payroll.pay');

    Route::post('admin/payroll/paymentSuccess', [PayrollController::class, 'paymentSuccess'])->name('payroll.payment_success');
    Route::match(['get', 'post'], 'admin/payroll/paymentRecord', [PayrollController::class, 'paymentRecord'])->name('payroll.payment_record');

    Route::get('admin/payroll/view/{id}', [PayrollController::class, 'view'])->whereNumber('id')->name('payroll.view');
    Route::match(['get', 'post'], 'admin/payroll/payslipView/{id?}', [PayrollController::class, 'payslipView'])->name('payroll.payslip_view');

    Route::get('admin/payroll/deletepayroll/{payslipid}/{month}/{year}/{role?}', [PayrollController::class, 'deletePayroll'])
        ->whereNumber('payslipid')
        ->name('payroll.delete');

    Route::get('admin/payroll/revertpayroll/{payslipid}/{month}/{year}/{role?}', [PayrollController::class, 'revertPayroll'])
        ->whereNumber('payslipid')
        ->name('payroll.revert');

    Route::match(['get', 'post'], 'admin/payroll/payrollreport', [PayrollReportController::class, 'index'])
        ->name('payroll.report');
});
