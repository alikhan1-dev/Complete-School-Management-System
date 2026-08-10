<?php

namespace App\Modules\Auth\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Auth\Requests\StaffLoginRequest;
use App\Modules\Auth\Services\LegacyPasswordVerifier;
use App\Modules\Staff\Models\Staff;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class StaffLoginController extends Controller
{
    public function __construct(protected LegacyPasswordVerifier $passwords)
    {
    }

    public function showLoginForm(): View
    {
        return view('auth::staff.login');
    }

    public function login(StaffLoginRequest $request): RedirectResponse
    {
        $credentials = $request->validated();

        $staff = Staff::query()->where('email', $credentials['email'])->first();

        if (! $staff || ! $this->passwords->check($credentials['password'], (string) $staff->password)) {
            return back()->withErrors(['email' => 'Invalid email or password.'])->onlyInput('email');
        }

        if ((int) $staff->is_active !== 1) {
            return back()->withErrors(['email' => 'This account is inactive.'])->onlyInput('email');
        }

        if ($this->passwords->needsRehash((string) $staff->password)) {
            $staff->password = $this->passwords->hash($credentials['password']);
            $staff->save();
        }

        Auth::guard('staff')->login($staff, $request->boolean('remember'));
        $request->session()->regenerate();

        $redirect = $request->session()->pull('redirect_to', route('admin.dashboard'));

        return redirect()->to($redirect);
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::guard('staff')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('staff.login');
    }
}
