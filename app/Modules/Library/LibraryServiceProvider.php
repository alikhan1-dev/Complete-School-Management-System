<?php

namespace App\Modules\Library;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class LibraryServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__ . '/Views', 'library');

        if (file_exists(__DIR__ . '/Routes/web.php')) {
            Route::middleware('web')->group(__DIR__ . '/Routes/web.php');
        }
    }
}
