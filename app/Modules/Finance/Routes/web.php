<?php

use App\Modules\Finance\Controllers\ExpenseController;
use App\Modules\Finance\Controllers\ExpenseHeadController;
use App\Modules\Finance\Controllers\ExpenseSearchController;
use App\Modules\Finance\Controllers\IncomeController;
use App\Modules\Finance\Controllers\IncomeHeadController;
use App\Modules\Finance\Controllers\IncomeSearchController;
use App\Modules\Finance\Controllers\ModuleStatusController;
use Illuminate\Support\Facades\Route;

Route::get('migration-status/finance', [ModuleStatusController::class, 'status'])->name('finance.migration_status');

Route::middleware(['staff.auth'])->group(function () {
    // Income heads (CI admin/incomehead)
    Route::get('admin/incomehead', [IncomeHeadController::class, 'index'])->name('finance.income_heads.index');
    Route::get('admin/incomehead/index', [IncomeHeadController::class, 'index']);
    Route::post('admin/incomehead', [IncomeHeadController::class, 'store'])->name('finance.income_heads.store');
    Route::post('admin/incomehead/create', [IncomeHeadController::class, 'store']);
    Route::get('admin/incomehead/edit/{id}', [IncomeHeadController::class, 'edit'])->name('finance.income_heads.edit');
    Route::post('admin/incomehead/edit/{id}', [IncomeHeadController::class, 'update'])->name('finance.income_heads.update');
    Route::get('admin/incomehead/delete/{id}', [IncomeHeadController::class, 'destroy'])->name('finance.income_heads.destroy');

    // Expense heads (CI admin/expensehead)
    Route::get('admin/expensehead', [ExpenseHeadController::class, 'index'])->name('finance.expense_heads.index');
    Route::get('admin/expensehead/index', [ExpenseHeadController::class, 'index']);
    Route::post('admin/expensehead', [ExpenseHeadController::class, 'store'])->name('finance.expense_heads.store');
    Route::post('admin/expensehead/create', [ExpenseHeadController::class, 'store']);
    Route::get('admin/expensehead/edit/{id}', [ExpenseHeadController::class, 'edit'])->name('finance.expense_heads.edit');
    Route::post('admin/expensehead/edit/{id}', [ExpenseHeadController::class, 'update'])->name('finance.expense_heads.update');
    Route::get('admin/expensehead/delete/{id}', [ExpenseHeadController::class, 'destroy'])->name('finance.expense_heads.destroy');

    // Income (CI admin/income)
    Route::get('admin/income', [IncomeController::class, 'index'])->name('finance.income.index');
    Route::get('admin/income/index', [IncomeController::class, 'index']);
    Route::post('admin/income', [IncomeController::class, 'store'])->name('finance.income.store');
    Route::post('admin/income/index', [IncomeController::class, 'store']);
    Route::get('admin/income/edit/{id}', [IncomeController::class, 'edit'])->name('finance.income.edit');
    Route::post('admin/income/edit/{id}', [IncomeController::class, 'update'])->name('finance.income.update');
    Route::get('admin/income/delete/{id}', [IncomeController::class, 'destroy'])->name('finance.income.destroy');
    Route::get('admin/income/download/{id}', [IncomeController::class, 'download'])->name('finance.income.download');

    // CI admin/income/incomeSearch — search by date / keyword
    Route::match(['get', 'post'], 'admin/income/incomesearch', [IncomeSearchController::class, 'index'])
        ->name('finance.income.search');

    // Expense (CI admin/expense)
    Route::get('admin/expense', [ExpenseController::class, 'index'])->name('finance.expense.index');
    Route::get('admin/expense/index', [ExpenseController::class, 'index']);
    Route::post('admin/expense', [ExpenseController::class, 'store'])->name('finance.expense.store');
    Route::post('admin/expense/index', [ExpenseController::class, 'store']);
    Route::get('admin/expense/edit/{id}', [ExpenseController::class, 'edit'])->name('finance.expense.edit');
    Route::post('admin/expense/edit/{id}', [ExpenseController::class, 'update'])->name('finance.expense.update');
    Route::get('admin/expense/delete/{id}', [ExpenseController::class, 'destroy'])->name('finance.expense.destroy');
    Route::get('admin/expense/download/{id}', [ExpenseController::class, 'download'])->name('finance.expense.download');

    // CI admin/expense/expenseSearch — search by date / keyword
    Route::match(['get', 'post'], 'admin/expense/expensesearch', [ExpenseSearchController::class, 'index'])
        ->name('finance.expense.search');
});
