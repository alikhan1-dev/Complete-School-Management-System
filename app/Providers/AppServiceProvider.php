<?php

namespace App\Providers;

use App\Modules\Roles\Services\PermissionService;
use App\Modules\Staff\Models\Staff;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(PermissionService::class);
    }

    public function boot(): void
    {
        Gate::before(function ($user, string $ability) {
            if ($user instanceof Staff && $user->isSuperAdmin()) {
                return true;
            }

            return null;
        });

        Gate::define('privilege', function ($user, string $category, string $permission = 'can_view') {
            return app(PermissionService::class)->hasPrivilege($category, $permission);
        });
    }
}
