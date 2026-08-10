<?php

namespace App\Modules\Shared;

use App\Modules\Shared\Services\SchoolContext;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class SharedServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(SchoolContext::class);
    }

    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__.'/Views', 'shared');

        View::composer(['shared::layouts.admin', 'shared::layouts.student_parent', 'shared::partials.admin_sidebar'], function ($view) {
            $view->with('schoolContext', app(SchoolContext::class));
        });

        if (file_exists(__DIR__.'/Routes/web.php')) {
            Route::middleware('web')->group(__DIR__.'/Routes/web.php');
        }
    }
}
