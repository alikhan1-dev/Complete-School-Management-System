<?php

namespace App\Modules\Shared\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class CheckLoginToken
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::guard('student_parent')->user();

        if ($user && ($user->role ?? null) === 'student') {
            $token = DB::table('users')->where('id', $user->id)->value('login_token');

            if (($user->login_token ?? null) !== null && $token !== null && $user->login_token !== $token) {
                Auth::guard('student_parent')->logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                return redirect()->route('student_parent.login');
            }
        }

        return $next($request);
    }
}
