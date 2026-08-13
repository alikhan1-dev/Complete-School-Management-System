<?php

use App\Modules\Library\Controllers\BookController;
use App\Modules\Library\Controllers\LibraryReportController;
use App\Modules\Library\Controllers\MemberController;
use App\Modules\Library\Controllers\ModuleStatusController;
use App\Modules\Library\Controllers\StudentBookController;
use Illuminate\Support\Facades\Route;

Route::get('migration-status/library', [ModuleStatusController::class, 'status'])->name('library.migration_status');

Route::middleware([
    'student_parent.auth',
    'student_parent.login_token',
    'student_parent.selected_class',
    'student_parent.permission:library',
])->group(function () {
    // CI user/book — catalog + issued books
    Route::get('user/book', [StudentBookController::class, 'index'])->name('user.library.books');
    Route::get('user/book/index', [StudentBookController::class, 'index']);
    Route::get('user/book/issue', [StudentBookController::class, 'issue'])->name('user.library.issue');
});

Route::middleware(['staff.auth'])->group(function () {
    // CI admin/book — catalog CRUD + CSV import
    Route::get('admin/book', [BookController::class, 'index'])->name('library.books.index');
    Route::get('admin/book/index', [BookController::class, 'index']);
    Route::get('admin/book/getall', [BookController::class, 'index'])->name('library.books.getall');
    Route::post('admin/book/create', [BookController::class, 'store'])->name('library.books.store');
    Route::get('admin/book/edit/{id}', [BookController::class, 'edit'])->whereNumber('id')->name('library.books.edit');
    Route::post('admin/book/edit/{id}', [BookController::class, 'update'])->whereNumber('id')->name('library.books.update');
    Route::get('admin/book/delete/{id}', [BookController::class, 'destroy'])->whereNumber('id')->name('library.books.destroy');
    Route::match(['get', 'post'], 'admin/book/import', [BookController::class, 'import'])
        ->name('library.books.import');
    Route::get('admin/book/exportformat', [BookController::class, 'exportFormat'])
        ->name('library.books.exportformat');

    // CI admin/member — list + enroll + surrender + issue/return
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
    Route::match(['get', 'post'], 'admin/member/issue/{id}', [MemberController::class, 'issue'])
        ->whereNumber('id')
        ->name('library.members.issue');
    Route::post('admin/member/bookreturn', [MemberController::class, 'returnBook'])
        ->name('library.members.return');

    // CI Report::library hub + reports (form search, no AJAX datatables)
    Route::get('report/library', [LibraryReportController::class, 'hub'])
        ->name('library.reports.hub');
    Route::match(['get', 'post'], 'report/studentbookissuereport', [LibraryReportController::class, 'bookIssue'])
        ->name('library.reports.book_issue');
    Route::match(['get', 'post'], 'report/bookduereport', [LibraryReportController::class, 'bookDue'])
        ->name('library.reports.book_due');
    Route::match(['get', 'post'], 'report/bookinventory', [LibraryReportController::class, 'bookInventory'])
        ->name('library.reports.book_inventory');
    Route::match(['get', 'post'], 'admin/book/issue_returnreport', [LibraryReportController::class, 'issueReturn'])
        ->name('library.reports.issue_return');
});
