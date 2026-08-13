<?php

use App\Modules\Hostel\Controllers\HostelController;
use App\Modules\Hostel\Controllers\HostelRoomController;
use App\Modules\Hostel\Controllers\ModuleStatusController;
use App\Modules\Hostel\Controllers\RoomTypeController;
use App\Modules\Hostel\Controllers\StudentHostelReportController;
use Illuminate\Support\Facades\Route;

Route::get('migration-status/hostel', [ModuleStatusController::class, 'status'])->name('hostel.migration_status');

Route::middleware(['staff.auth'])->group(function () {
    // CI admin/roomtype
    Route::get('admin/roomtype', [RoomTypeController::class, 'index'])->name('hostel.room_types.index');
    Route::get('admin/roomtype/index', [RoomTypeController::class, 'index']);
    Route::post('admin/roomtype/create', [RoomTypeController::class, 'store'])->name('hostel.room_types.store');
    Route::get('admin/roomtype/edit/{id}', [RoomTypeController::class, 'edit'])->whereNumber('id')->name('hostel.room_types.edit');
    Route::post('admin/roomtype/edit/{id}', [RoomTypeController::class, 'update'])->whereNumber('id')->name('hostel.room_types.update');
    Route::get('admin/roomtype/delete/{id}', [RoomTypeController::class, 'destroy'])->whereNumber('id')->name('hostel.room_types.destroy');

    // CI admin/hostel
    Route::get('admin/hostel', [HostelController::class, 'index'])->name('hostel.hostels.index');
    Route::get('admin/hostel/index', [HostelController::class, 'index']);
    Route::post('admin/hostel/create', [HostelController::class, 'store'])->name('hostel.hostels.store');
    Route::get('admin/hostel/edit/{id}', [HostelController::class, 'edit'])->whereNumber('id')->name('hostel.hostels.edit');
    Route::post('admin/hostel/edit/{id}', [HostelController::class, 'update'])->whereNumber('id')->name('hostel.hostels.update');
    Route::get('admin/hostel/delete/{id}', [HostelController::class, 'destroy'])->whereNumber('id')->name('hostel.hostels.destroy');

    // CI admin/hostelroom
    Route::get('admin/hostelroom', [HostelRoomController::class, 'index'])->name('hostel.rooms.index');
    Route::get('admin/hostelroom/index', [HostelRoomController::class, 'index']);
    Route::post('admin/hostelroom/create', [HostelRoomController::class, 'store'])->name('hostel.rooms.store');
    Route::get('admin/hostelroom/edit/{id}', [HostelRoomController::class, 'edit'])->whereNumber('id')->name('hostel.rooms.edit');
    Route::post('admin/hostelroom/edit/{id}', [HostelRoomController::class, 'update'])->whereNumber('id')->name('hostel.rooms.update');
    Route::get('admin/hostelroom/delete/{id}', [HostelRoomController::class, 'destroy'])->whereNumber('id')->name('hostel.rooms.destroy');
    Route::get('admin/hostelroom/getRoom', [HostelRoomController::class, 'getRoom'])->name('hostel.rooms.by_hostel');

    // CI admin/hostelroom/studenthosteldetails — student hostel report (form POST; DataTables AJAX deferred)
    Route::match(['get', 'post'], 'admin/hostelroom/studenthosteldetails', [StudentHostelReportController::class, 'index'])
        ->name('hostel.reports.student_hostel');
});
