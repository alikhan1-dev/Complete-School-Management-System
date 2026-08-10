<?php

namespace App\Modules\Shared\Middleware;

use App\Modules\Roles\Services\PermissionService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Gate student/parent portal features using permission_student table.
 */
class CheckStudentParentPermission
{
    public function __construct(protected PermissionService $permissions)
    {
    }

    public function handle(Request $request, Closure $next, string $shortCode): Response
    {
        $user = Auth::guard('student_parent')->user();

        if (! $user) {
            return redirect()->route('student_parent.login');
        }

        $role = (string) ($user->role ?? 'student');

        if (! $this->permissions->studentParentHas($shortCode, $role)) {
            abort(403, 'Unauthorized');
        }

        return $next($request);
    }
}
