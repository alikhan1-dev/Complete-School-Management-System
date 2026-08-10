<?php

namespace App\Modules\Shared\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class RequireSelectedClass
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::guard('student_parent')->user();

        if ($user && ($user->role ?? null) !== 'guest' && ! $request->session()->has('current_class')) {
            $routeName = $request->route()?->getName();
            if ($routeName !== 'student_parent.choose_class') {
                return redirect()->route('student_parent.choose_class');
            }
        }

        return $next($request);
    }
}
