<?php

namespace App\Modules\Parents;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class ParentsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__ . '/Views', 'parents');

        if (file_exists(__DIR__ . '/Routes/web.php')) {
            Route::middleware('web')->group(__DIR__ . '/Routes/web.php');
        }
    }
}
