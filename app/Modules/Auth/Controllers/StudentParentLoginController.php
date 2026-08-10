<?php

namespace App\Modules\Auth\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Auth\Models\PortalUser;
use App\Modules\Auth\Requests\StudentParentLoginRequest;
use App\Modules\Auth\Services\LegacyPasswordVerifier;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\View\View;

class StudentParentLoginController extends Controller
{
    public function __construct(protected LegacyPasswordVerifier $passwords)
    {
    }

    public function showLoginForm(): View
    {
        return view('auth::student_parent.login');
    }

    public function login(StudentParentLoginRequest $request): RedirectResponse
    {
        $credentials = $request->validated();

        $user = PortalUser::query()->where('username', $credentials['username'])->first();

        if (! $user || ! $this->passwords->check($credentials['password'], (string) $user->password)) {
            return back()->withErrors(['username' => 'Invalid username or password.'])->onlyInput('username');
        }

        if (! $user->is_active) {
            return back()->withErrors(['username' => 'This account is inactive.'])->onlyInput('username');
        }

        if ($this->passwords->needsRehash((string) $user->password)) {
            $user->password = $this->passwords->hash($credentials['password']);
        }

        if ($user->role === 'student') {
            $user->login_token = Str::random(40);
        }

        $user->save();

        Auth::guard('student_parent')->login($user);
        $request->session()->regenerate();

        // Guest role skips class selection.
        if ($user->role === 'guest') {
            $request->session()->put('current_class', ['student_session_id' => 0]);
        }

        $redirect = $request->session()->pull('redirect_to_user', route('student_parent.dashboard'));

        return redirect()->to($redirect);
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::guard('student_parent')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('student_parent.login');
    }

    public function chooseClass(): View
    {
        return view('auth::student_parent.choose_class');
    }

    public function storeClass(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'student_session_id' => ['required', 'integer'],
        ]);

        $request->session()->put('current_class', [
            'student_session_id' => (int) $data['student_session_id'],
        ]);

        return redirect()->route('student_parent.dashboard');
    }
}
