<?php

namespace App\Modules\Shared\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    public function handle(Request $request, Closure $next): Response
    {
        $locale = session('locale');

        if (! $locale && Auth::guard('staff')->check()) {
            $locale = session('staff_locale');
        }

        if (! $locale && Auth::guard('student_parent')->check()) {
            $locale = session('portal_locale');
        }

        $locale = $locale ?: config('app.locale', 'en');
        App::setLocale($locale);

        return $next($request);
    }
}
