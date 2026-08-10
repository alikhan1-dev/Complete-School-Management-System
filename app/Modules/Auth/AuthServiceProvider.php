<?php

namespace App\Modules\Auth;

use App\Modules\Auth\Controllers\DashboardController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__.'/Views', 'auth');

        Route::middleware('web')->group(__DIR__.'/Routes/web.php');

        Route::middleware(['web', 'staff.auth'])->group(function () {
            Route::get('admin/admin/dashboard', [DashboardController::class, 'admin'])->name('admin.dashboard');
        });

        Route::middleware(['web', 'student_parent.auth', 'student_parent.login_token', 'student_parent.selected_class'])->group(function () {
            Route::get('user/user/dashboard', [DashboardController::class, 'studentParent'])->name('student_parent.dashboard');
        });
    }
}
