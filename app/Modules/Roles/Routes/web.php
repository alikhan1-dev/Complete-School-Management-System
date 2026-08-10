<?php

use App\Modules\Roles\Controllers\RoleController;
use App\Modules\Roles\Controllers\StudentPermissionController;
use Illuminate\Support\Facades\Route;

Route::middleware(['staff.auth'])->prefix('admin')->group(function () {
    Route::get('roles', [RoleController::class, 'index'])->middleware('permission:superadmin,can_view')->name('roles.index');
    Route::get('roles/datatable', [RoleController::class, 'datatable'])->middleware('permission:superadmin,can_view')->name('roles.datatable');
    // Static paths before {role} binding.
    Route::get('roles/student-permissions', [StudentPermissionController::class, 'index'])->middleware('permission:superadmin,can_view')->name('roles.student_permissions');
    Route::get('roles/student-permissions/datatable', [StudentPermissionController::class, 'datatable'])->middleware('permission:superadmin,can_view')->name('roles.student_permissions.datatable');
    Route::get('roles/{role}/permissions', [RoleController::class, 'permissions'])->middleware('permission:superadmin,can_edit')->name('roles.permissions');
    Route::post('roles/{role}/permissions', [RoleController::class, 'updatePermissions'])->middleware('permission:superadmin,can_edit')->name('roles.permissions.update');
});
