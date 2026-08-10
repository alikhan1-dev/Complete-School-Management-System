<?php

namespace App\Modules\Staff;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class StaffServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__ . '/Views', 'staff');

        if (file_exists(__DIR__ . '/Routes/web.php')) {
            Route::middleware('web')->group(__DIR__ . '/Routes/web.php');
        }
    }
}
