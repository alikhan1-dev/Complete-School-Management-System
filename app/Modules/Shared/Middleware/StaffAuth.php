<?php

namespace App\Modules\Shared\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class StaffAuth
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! Auth::guard('staff')->check()) {
            $request->session()->put('redirect_to', $request->fullUrl());

            return redirect()->route('staff.login');
        }

        $staff = Auth::guard('staff')->user();

        if ((int) ($staff->is_active ?? 0) !== 1) {
            Auth::guard('staff')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('staff.login');
        }

        return $next($request);
    }
}
