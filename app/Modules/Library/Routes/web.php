<?php

use App\Modules\Library\Controllers\BookController;
use App\Modules\Library\Controllers\ModuleStatusController;
use Illuminate\Support\Facades\Route;

Route::get('migration-status/library', [ModuleStatusController::class, 'status'])->name('library.migration_status');

Route::middleware(['staff.auth'])->group(function () {
    // CI admin/book — catalog CRUD (import / members / issue deferred)
    Route::get('admin/book', [BookController::class, 'index'])->name('library.books.index');
    Route::get('admin/book/index', [BookController::class, 'index']);
    // Sidebar primary list URL
    Route::get('admin/book/getall', [BookController::class, 'index'])->name('library.books.getall');
    Route::post('admin/book/create', [BookController::class, 'store'])->name('library.books.store');
    Route::get('admin/book/edit/{id}', [BookController::class, 'edit'])->whereNumber('id')->name('library.books.edit');
    Route::post('admin/book/edit/{id}', [BookController::class, 'update'])->whereNumber('id')->name('library.books.update');
    Route::get('admin/book/delete/{id}', [BookController::class, 'destroy'])->whereNumber('id')->name('library.books.destroy');
});
