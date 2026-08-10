<?php

namespace App\Modules\Auth\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Auth\Models\PortalUser;
use App\Modules\Auth\Requests\ForgotPasswordRequest;
use App\Modules\Auth\Requests\PortalForgotPasswordRequest;
use App\Modules\Auth\Requests\ResetPasswordRequest;
use App\Modules\Auth\Services\LegacyPasswordVerifier;
use App\Modules\Staff\Models\Staff;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Str;
use Illuminate\View\View;

class PasswordResetController extends Controller
{
    public function __construct(protected LegacyPasswordVerifier $passwords)
    {
    }

    public function showStaffForgotForm(): View
    {
        return view('auth::staff.forgot_password');
    }

    public function sendStaffResetLink(ForgotPasswordRequest $request): RedirectResponse
    {
        $staff = Staff::query()->where('email', $request->validated('email'))->first();

        if (! $staff) {
            return back()->withErrors(['email' => 'Incorrect email.'])->withInput();
        }

        if ((int) $staff->is_active !== 1) {
            return redirect()->route('staff.login')
                ->with('disable_message', 'Your account is disabled. Please contact the administrator.');
        }

        $staff->verification_code = Str::random(40);
        $staff->save();

        // Email/SMS sending is deferred to Communication module migration.
        // Link is flashed for local parity testing when mail is not configured.
        $resetLink = route('staff.reset_password', ['verification_code' => $staff->verification_code]);

        return redirect()->route('staff.login')
            ->with('message', 'Please check your email to recover your password.')
            ->with('reset_link_debug', $resetLink);
    }

    public function showStaffResetForm(string $verification_code): View|RedirectResponse
    {
        $staff = Staff::query()->where('verification_code', $verification_code)->first();

        if (! $staff) {
            return redirect()->route('staff.forgot_password')->with('message', 'Invalid link.');
        }

        return view('auth::staff.reset_password', ['verification_code' => $verification_code]);
    }

    public function resetStaffPassword(ResetPasswordRequest $request, string $verification_code): RedirectResponse
    {
        $staff = Staff::query()->where('verification_code', $verification_code)->first();

        if (! $staff) {
            return redirect()->route('staff.forgot_password')->with('message', 'Invalid link.');
        }

        $staff->password = $this->passwords->hash($request->validated('password'));
        $staff->verification_code = '';
        $staff->save();

        return redirect()->route('staff.login')->with('message', 'Password reset successfully.');
    }

    public function showPortalForgotForm(): View
    {
        return view('auth::student_parent.forgot_password');
    }

    public function sendPortalResetLink(PortalForgotPasswordRequest $request): RedirectResponse
    {
        $role = $request->validated('user')[0];
        $user = PortalUser::query()
            ->where('username', $request->validated('username'))
            ->where('role', $role)
            ->first();

        if (! $user) {
            return back()->withErrors(['username' => 'Incorrect username.'])->withInput();
        }

        $user->verification_code = Str::random(40);
        $user->save();

        $resetLink = route('student_parent.reset_password', [
            'role' => $user->role,
            'verification_code' => $user->verification_code,
        ]);

        return redirect()->route('student_parent.login')
            ->with('message', 'Please check your email to recover your password.')
            ->with('reset_link_debug', $resetLink);
    }

    public function showPortalResetForm(string $role, string $verification_code): View|RedirectResponse
    {
        $user = PortalUser::query()
            ->where('role', $role)
            ->where('verification_code', $verification_code)
            ->first();

        if (! $user) {
            return redirect()->route('student_parent.forgot_password')->with('message', 'Invalid link.');
        }

        return view('auth::student_parent.reset_password', [
            'role' => $role,
            'verification_code' => $verification_code,
        ]);
    }

    public function resetPortalPassword(ResetPasswordRequest $request, string $role, string $verification_code): RedirectResponse
    {
        $user = PortalUser::query()
            ->where('role', $role)
            ->where('verification_code', $verification_code)
            ->first();

        if (! $user) {
            return redirect()->route('student_parent.forgot_password')->with('message', 'Invalid link.');
        }

        // Store bcrypt rather than CI plaintext after reset.
        $user->password = $this->passwords->hash($request->validated('password'));
        $user->verification_code = '';
        $user->save();

        return redirect()->route('student_parent.login')->with('message', 'Password reset successfully.');
    }
}
