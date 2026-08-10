<?php

namespace App\Modules\Shared\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class StudentParentAuth
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! Auth::guard('student_parent')->check()) {
            $request->session()->put('redirect_to_user', $request->fullUrl());

            return redirect()->route('student_parent.login');
        }

        return $next($request);
    }
}
