<?php

namespace App\Modules\Shared\Middleware;

use App\Modules\Roles\Services\PermissionService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckPermission
{
    public function __construct(protected PermissionService $permissions)
    {
    }

    public function handle(Request $request, Closure $next, string $category, string $ability = 'can_view'): Response
    {
        if (! $this->permissions->hasPrivilege($category, $ability)) {
            abort(403, 'Unauthorized');
        }

        return $next($request);
    }
}
