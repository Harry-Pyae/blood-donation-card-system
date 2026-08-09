<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Contracts\Auth\StatefulGuard;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\View\View;

class StaffLoginController extends Controller
{
    public function showLoginForm(Request $request): View|RedirectResponse
    {
        $guard = $this->guard();

        if ($guard->check()) {
            $user = $guard->user();

            if ($user instanceof User && $user->canAccessStaffWorkspace()) {
                return redirect()->to($user->isLaboratoryUser() ? route('bloodcare.lab.dashboard') : backpack_url('dashboard'));
            }

            $this->logoutSession($request);
        }

        return view(backpack_view('auth.login'), [
            'title' => trans('backpack::base.login'),
            'username' => config('backpack.base.authentication_column', 'email'),
        ]);
    }

    public function login(Request $request): RedirectResponse
    {
        $guard = $this->guard();
        $column = config('backpack.base.authentication_column', 'email');
        $identifier = Str::lower(trim((string) $request->input($column)));
        $request->merge([$column => $identifier]);

        $credentials = $request->validate([
            $column => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        $throttleKey = Str::transliterate($identifier.'|'.$request->ip());
        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            $seconds = RateLimiter::availableIn($throttleKey);

            return back()
                ->withErrors([$column => __('bloodcare.login.account_throttled', [
                    'seconds' => $seconds,
                ])])
                ->onlyInput($column);
        }

        if (! $guard->attempt($credentials, $request->boolean('remember'))) {
            RateLimiter::hit($throttleKey, 60);

            return back()
                ->withErrors([$column => trans('auth.failed')])
                ->onlyInput($column);
        }

        $request->session()->regenerate();
        $user = $guard->user();

        if (! $user instanceof User || ! $user->canAccessStaffWorkspace()) {
            $message = $this->denialMessage($user);
            $this->logoutSession($request);

            return redirect()
                ->route('backpack.auth.login')
                ->withErrors([$column => $message])
                ->withInput([$column => $identifier]);
        }

        RateLimiter::clear($throttleKey);

        if ($user->hasTwoFactorEnabled()) {
            $remember = $request->boolean('remember');
            $guard->logout();
            $request->session()->regenerate();
            $request->session()->put('two_factor.login', [
                'user_id' => $user->id,
                'remember' => $remember,
                'context' => 'staff',
                'issued_at' => time(),
            ]);

            return redirect()->route('two-factor.challenge');
        }

        // Backpack and the rest of BloodCare now use this same canonical
        // session guard. Do not create a second authentication identity here.
        $request->setUserResolver(static fn () => $user);
        $request->session()->forget('url.intended');

        return redirect()->to($user->isLaboratoryUser() ? route('bloodcare.lab.dashboard') : backpack_url('dashboard'));
    }

    public function logout(Request $request): RedirectResponse
    {
        $this->logoutSession($request);

        return redirect()->route('backpack.auth.login');
    }

    private function guard(): StatefulGuard
    {
        $guardName = (string) config('backpack.base.guard', 'web');
        Auth::shouldUse($guardName);

        /** @var StatefulGuard $guard */
        $guard = Auth::guard($guardName);

        return $guard;
    }

    private function logoutSession(Request $request): void
    {
        Auth::guard((string) config('backpack.base.guard', 'web'))->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();
    }

    private function denialMessage(?User $user): string
    {
        if ((bool) $user?->is_banned) {
            return __('bloodcare.login.account_banned');
        }

        return match (strtolower(trim((string) $user?->approval_status))) {
            User::APPROVAL_PENDING => __('bloodcare.login.account_pending'),
            User::APPROVAL_REJECTED => __('bloodcare.login.account_rejected'),
            default => __('bloodcare.login.account_unauthorized'),
        };
    }
}
