<?php

use App\Modules\Library\Controllers\BookController;
use App\Modules\Library\Controllers\MemberController;
use App\Modules\Library\Controllers\ModuleStatusController;
use Illuminate\Support\Facades\Route;

Route::get('migration-status/library', [ModuleStatusController::class, 'status'])->name('library.migration_status');

Route::middleware(['staff.auth'])->group(function () {
    // CI admin/book — catalog CRUD (import deferred)
    Route::get('admin/book', [BookController::class, 'index'])->name('library.books.index');
    Route::get('admin/book/index', [BookController::class, 'index']);
    Route::get('admin/book/getall', [BookController::class, 'index'])->name('library.books.getall');
    Route::post('admin/book/create', [BookController::class, 'store'])->name('library.books.store');
    Route::get('admin/book/edit/{id}', [BookController::class, 'edit'])->whereNumber('id')->name('library.books.edit');
    Route::post('admin/book/edit/{id}', [BookController::class, 'update'])->whereNumber('id')->name('library.books.update');
    Route::get('admin/book/delete/{id}', [BookController::class, 'destroy'])->whereNumber('id')->name('library.books.destroy');

    // CI admin/member — list + enroll + surrender (issue/return deferred)
    Route::get('admin/member', [MemberController::class, 'index'])->name('library.members.index');
    Route::get('admin/member/index', [MemberController::class, 'index']);
    Route::match(['get', 'post'], 'admin/member/student', [MemberController::class, 'students'])
        ->name('library.members.students');
    Route::post('admin/member/add', [MemberController::class, 'storeStudent'])
        ->name('library.members.students.store');
    Route::get('admin/member/teacher', [MemberController::class, 'teachers'])
        ->name('library.members.teachers');
    Route::post('admin/member/addteacher', [MemberController::class, 'storeTeacher'])
        ->name('library.members.teachers.store');
    Route::get('admin/member/surrender/{id}', [MemberController::class, 'surrender'])
        ->whereNumber('id')
        ->name('library.members.surrender');
});
