<?php

namespace App\Modules\Shared\Middleware;

use App\Modules\Roles\Models\PermissionGroup;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckModuleEnabled
{
    public function handle(Request $request, Closure $next, string $shortCode): Response
    {
        $active = PermissionGroup::query()
            ->where('short_code', $shortCode)
            ->where('is_active', 1)
            ->exists();

        if (! $active) {
            abort(403, 'Module is disabled');
        }

        return $next($request);
    }
}
