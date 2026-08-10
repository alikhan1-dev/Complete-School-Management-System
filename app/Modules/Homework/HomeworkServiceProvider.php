<?php

namespace App\Modules\Homework;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class HomeworkServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__ . '/Views', 'homework');

        if (file_exists(__DIR__ . '/Routes/web.php')) {
            Route::middleware('web')->group(__DIR__ . '/Routes/web.php');
        }
    }
}
