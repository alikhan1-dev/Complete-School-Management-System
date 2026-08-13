<?php

use App\Modules\Transport\Controllers\ModuleStatusController;
use App\Modules\Transport\Controllers\PickupPointController;
use App\Modules\Transport\Controllers\RouteController;
use App\Modules\Transport\Controllers\RoutePickupPointController;
use App\Modules\Transport\Controllers\StudentTransportReportController;
use App\Modules\Transport\Controllers\VehicleController;
use App\Modules\Transport\Controllers\VehrouteController;
use Illuminate\Support\Facades\Route;

Route::get('migration-status/transport', [ModuleStatusController::class, 'status'])->name('transport.migration_status');

Route::middleware(['staff.auth'])->group(function () {
    // CI admin/vehicle — form CRUD (AJAX modals replaced)
    Route::get('admin/vehicle', [VehicleController::class, 'index'])->name('transport.vehicles.index');
    Route::get('admin/vehicle/index', [VehicleController::class, 'index']);
    Route::post('admin/vehicle/add', [VehicleController::class, 'store'])->name('transport.vehicles.store');
    Route::get('admin/vehicle/edit/{id}', [VehicleController::class, 'edit'])->whereNumber('id')->name('transport.vehicles.edit');
    Route::post('admin/vehicle/edit/{id}', [VehicleController::class, 'update'])->whereNumber('id')->name('transport.vehicles.update');
    Route::get('admin/vehicle/view/{id}', [VehicleController::class, 'show'])->whereNumber('id')->name('transport.vehicles.show');
    Route::get('admin/vehicle/delete/{id}', [VehicleController::class, 'destroy'])->whereNumber('id')->name('transport.vehicles.destroy');

    // CI admin/route — title CRUD
    Route::get('admin/route', [RouteController::class, 'index'])->name('transport.routes.index');
    Route::get('admin/route/index', [RouteController::class, 'index']);
    Route::post('admin/route/create', [RouteController::class, 'store'])->name('transport.routes.store');
    Route::get('admin/route/edit/{id}', [RouteController::class, 'edit'])->whereNumber('id')->name('transport.routes.edit');
    Route::post('admin/route/edit/{id}', [RouteController::class, 'update'])->whereNumber('id')->name('transport.routes.update');
    Route::get('admin/route/delete/{id}', [RouteController::class, 'destroy'])->whereNumber('id')->name('transport.routes.destroy');

    // CI admin/route/studenttransportdetails — student transport report
    Route::match(['get', 'post'], 'admin/route/studenttransportdetails', [StudentTransportReportController::class, 'index'])
        ->name('transport.reports.student_transport');

    // CI admin/vehroute — assign vehicles on routes
    Route::match(['get', 'post'], 'admin/vehroute', [VehrouteController::class, 'index'])
        ->name('transport.vehroute.index');
    Route::get('admin/vehroute/index', [VehrouteController::class, 'index']);
    Route::match(['get', 'post'], 'admin/vehroute/edit/{id}', [VehrouteController::class, 'edit'])
        ->whereNumber('id')
        ->name('transport.vehroute.edit');
    Route::get('admin/vehroute/delete/{id}', [VehrouteController::class, 'destroy'])
        ->whereNumber('id')
        ->name('transport.vehroute.destroy');

    // CI admin/pickuppoint — master CRUD (route assign / student fees / map deferred)
    Route::get('admin/pickuppoint', [PickupPointController::class, 'index'])->name('transport.pickup_points.index');
    Route::get('admin/pickuppoint/index', [PickupPointController::class, 'index']);
    Route::post('admin/pickuppoint/add_point', [PickupPointController::class, 'store'])->name('transport.pickup_points.store');
    Route::get('admin/pickuppoint/edit/{id}', [PickupPointController::class, 'edit'])->whereNumber('id')->name('transport.pickup_points.edit');
    Route::post('admin/pickuppoint/edit/{id}', [PickupPointController::class, 'update'])->whereNumber('id')->name('transport.pickup_points.update');
    Route::get('admin/pickuppoint/delete_point/{id}', [PickupPointController::class, 'destroy'])
        ->whereNumber('id')
        ->name('transport.pickup_points.destroy');

    // CI admin/pickuppoint/assign — route pickup points (reorder / student fees deferred)
    Route::get('admin/pickuppoint/assign', [RoutePickupPointController::class, 'index'])
        ->name('transport.route_pickup.index');
    Route::match(['get', 'post'], 'admin/pickuppoint/assign/create', [RoutePickupPointController::class, 'create'])
        ->name('transport.route_pickup.create');
    Route::match(['get', 'post'], 'admin/pickuppoint/assign/edit/{id}', [RoutePickupPointController::class, 'edit'])
        ->whereNumber('id')
        ->name('transport.route_pickup.edit');
    Route::get('admin/pickuppoint/delete/{id}', [RoutePickupPointController::class, 'destroy'])
        ->whereNumber('id')
        ->name('transport.route_pickup.destroy');

    // CI admin/pickuppoint/getpickuppointsbyroute — report / assign cascading dropdowns
    Route::post('admin/pickuppoint/getpickuppointsbyroute', [StudentTransportReportController::class, 'pickupPointsByRoute'])
        ->name('transport.route_pickup.by_route');
});
