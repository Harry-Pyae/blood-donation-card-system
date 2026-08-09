<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\TwoFactorAuthenticationService;
use Illuminate\Contracts\Auth\StatefulGuard;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\View\View;

class TwoFactorChallengeController extends Controller
{
    public function show(Request $request): View|RedirectResponse
    {
        $context = (string) data_get($request->session()->get('two_factor.login'), 'context', 'staff');
        $pending = $this->pendingLogin($request);
        if ($pending === null) {
            return $this->expiredRedirect($request, $context);
        }

        $user = User::query()->find($pending['user_id']);
        if (! $user instanceof User || ! $user->hasTwoFactorEnabled() || ! $this->stillAllowed($user, $pending['context'])) {
            $request->session()->forget('two_factor.login');

            return $this->expiredRedirect($request, $pending['context']);
        }

        return view('auth.two-factor-challenge', [
            'email' => $user->email,
            'context' => $pending['context'],
        ]);
    }

    public function verify(Request $request, TwoFactorAuthenticationService $twoFactor): RedirectResponse
    {
        $data = $request->validate(['code' => ['required', 'string', 'max:32']]);
        $context = (string) data_get($request->session()->get('two_factor.login'), 'context', 'staff');
        $pending = $this->pendingLogin($request);
        if ($pending === null) {
            return $this->expiredRedirect($request, $context);
        }

        $user = User::query()->find($pending['user_id']);
        if (! $user instanceof User || ! $user->hasTwoFactorEnabled() || ! $this->stillAllowed($user, $pending['context'])) {
            $request->session()->forget('two_factor.login');

            return $this->expiredRedirect($request, $pending['context']);
        }

        $throttleKey = '2fa|'.$user->id.'|'.$request->ip();
        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            return back()->withErrors(['code' => __('bloodcare.two_factor.too_many_attempts')]);
        }

        if (! $twoFactor->verifyAndConsume($user, (string) $data['code'])) {
            RateLimiter::hit($throttleKey, 60);

            return back()->withErrors(['code' => __('bloodcare.two_factor.invalid_code')]);
        }

        RateLimiter::clear($throttleKey);
        $guard = $this->guard();
        $guard->login($user, (bool) ($pending['remember'] ?? false));
        $request->session()->regenerate();
        $request->session()->forget('two_factor.login');

        return redirect()->to($this->destination($user, $pending['context']));
    }

    public function cancel(Request $request): RedirectResponse
    {
        $context = (string) data_get($request->session()->get('two_factor.login'), 'context', 'staff');
        $request->session()->forget('two_factor.login');

        return redirect()->to($context === 'hospital' ? route('hospital.login') : route('backpack.auth.login'));
    }

    /** @return array{user_id:int,remember:bool,context:string,issued_at:int}|null */
    private function pendingLogin(Request $request): ?array
    {
        $pending = $request->session()->get('two_factor.login');
        if (! is_array($pending)
            || ! isset($pending['user_id'], $pending['context'], $pending['issued_at'])
            || time() - (int) $pending['issued_at'] > (int) config('bloodcare.two_factor.challenge_ttl', 300)) {
            $request->session()->forget('two_factor.login');

            return null;
        }

        return [
            'user_id' => (int) $pending['user_id'],
            'remember' => (bool) ($pending['remember'] ?? false),
            'context' => (string) $pending['context'],
            'issued_at' => (int) $pending['issued_at'],
        ];
    }

    private function stillAllowed(User $user, string $context): bool
    {
        return $context === 'hospital' ? $user->isHospitalUser() : $user->canAccessStaffWorkspace();
    }

    private function destination(User $user, string $context): string
    {
        if ($context === 'hospital') {
            return route('hospital.dashboard');
        }

        return $user->isLaboratoryUser() ? route('bloodcare.lab.dashboard') : backpack_url('dashboard');
    }

    private function expiredRedirect(Request $request, string $context = 'staff'): RedirectResponse
    {
        $route = $context === 'hospital' ? 'hospital.login' : 'backpack.auth.login';

        return redirect()->route($route)->withErrors(['email' => __('bloodcare.two_factor.challenge_expired')]);
    }

    private function guard(): StatefulGuard
    {
        $guardName = (string) config('backpack.base.guard', 'web');
        Auth::shouldUse($guardName);

        /** @var StatefulGuard $guard */
        $guard = Auth::guard($guardName);

        return $guard;
    }
}
