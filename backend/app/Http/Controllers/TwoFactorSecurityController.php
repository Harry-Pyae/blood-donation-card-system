<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\User;
use App\Services\TwoFactorAuthenticationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class TwoFactorSecurityController extends Controller
{
    public function manage(Request $request): View
    {
        $user = $this->authorizedUser($request);

        return view('security.two-factor-manage', [
            'user' => $user,
            'backUrl' => $this->backUrl($user),
        ]);
    }

    public function startSetup(Request $request, TwoFactorAuthenticationService $twoFactor): RedirectResponse
    {
        $user = $this->authorizedUser($request);
        $data = $request->validate(['current_password' => ['required', 'string']]);
        $this->verifyPassword($user, (string) $data['current_password']);

        if ($user->hasTwoFactorEnabled()) {
            return redirect()->route('two-factor.manage')->with('status', __('bloodcare.two_factor.already_enabled'));
        }

        $request->session()->put('two_factor.setup', [
            'secret' => $twoFactor->generateSecret(),
            'issued_at' => time(),
        ]);

        return redirect()->route('two-factor.setup');
    }

    public function setup(Request $request, TwoFactorAuthenticationService $twoFactor): View|RedirectResponse
    {
        $user = $this->authorizedUser($request);
        if ($user->hasTwoFactorEnabled()) {
            return redirect()->route('two-factor.manage');
        }

        $pending = $this->pendingSetup($request);
        if ($pending === null) {
            return redirect()->route('two-factor.manage')->withErrors(['current_password' => __('bloodcare.two_factor.setup_expired')]);
        }

        return view('security.two-factor-setup', [
            'user' => $user,
            'secret' => $pending['secret'],
            'provisioningUri' => $twoFactor->provisioningUri($user, $pending['secret']),
            'backUrl' => route('two-factor.manage'),
        ]);
    }

    public function confirm(Request $request, TwoFactorAuthenticationService $twoFactor): RedirectResponse
    {
        $user = $this->authorizedUser($request);
        $data = $request->validate(['code' => ['required', 'string', 'max:16']]);
        $pending = $this->pendingSetup($request);
        if ($pending === null) {
            return redirect()->route('two-factor.manage')->withErrors(['current_password' => __('bloodcare.two_factor.setup_expired')]);
        }

        $step = $twoFactor->matchingStep($pending['secret'], (string) $data['code']);
        if ($step === null) {
            throw ValidationException::withMessages(['code' => __('bloodcare.two_factor.invalid_code')]);
        }

        $codes = $twoFactor->generateRecoveryCodes();
        $user->forceFill([
            'two_factor_secret' => $pending['secret'],
            'two_factor_recovery_codes' => $twoFactor->hashRecoveryCodes($codes),
            'two_factor_confirmed_at' => now(),
            'two_factor_last_used_step' => $step,
        ])->save();
        $request->session()->forget('two_factor.setup');
        $this->record($user, 'Two-factor authentication enabled', 'Enabled');

        return redirect()->route('two-factor.recovery-codes')
            ->with('two_factor.recovery_codes_once', $codes)
            ->with('status', __('bloodcare.two_factor.enabled_success'));
    }

    public function recoveryCodes(Request $request): View|RedirectResponse
    {
        $user = $this->authorizedUser($request);
        $codes = $request->session()->pull('two_factor.recovery_codes_once');
        if (! is_array($codes) || $codes === []) {
            return redirect()->route('two-factor.manage');
        }

        return view('security.two-factor-recovery-codes', [
            'user' => $user,
            'codes' => $codes,
            'backUrl' => route('two-factor.manage'),
        ]);
    }

    public function regenerateRecoveryCodes(Request $request, TwoFactorAuthenticationService $twoFactor): RedirectResponse
    {
        $user = $this->authorizedUser($request);
        $this->requireEnabled($user);
        $data = $request->validate([
            'current_password' => ['required', 'string'],
            'code' => ['required', 'string', 'max:32'],
        ]);
        $this->verifyPassword($user, (string) $data['current_password']);
        $this->verifySecondFactor($user, (string) $data['code'], $twoFactor);

        $codes = $twoFactor->generateRecoveryCodes();
        $user->forceFill(['two_factor_recovery_codes' => $twoFactor->hashRecoveryCodes($codes)])->save();
        $this->record($user, 'Two-factor recovery codes regenerated', 'Regenerated');

        return redirect()->route('two-factor.recovery-codes')
            ->with('two_factor.recovery_codes_once', $codes)
            ->with('status', __('bloodcare.two_factor.recovery_regenerated'));
    }

    public function disable(Request $request, TwoFactorAuthenticationService $twoFactor): RedirectResponse
    {
        $user = $this->authorizedUser($request);
        $this->requireEnabled($user);
        $data = $request->validate([
            'current_password' => ['required', 'string'],
            'code' => ['required', 'string', 'max:32'],
        ]);
        $this->verifyPassword($user, (string) $data['current_password']);
        $this->verifySecondFactor($user, (string) $data['code'], $twoFactor);

        $user->forceFill([
            'two_factor_secret' => null,
            'two_factor_recovery_codes' => null,
            'two_factor_confirmed_at' => null,
            'two_factor_last_used_step' => null,
        ])->save();
        $this->record($user, 'Two-factor authentication disabled', 'Disabled');

        return redirect()->route('two-factor.manage')->with('status', __('bloodcare.two_factor.disabled_success'));
    }

    private function authorizedUser(Request $request): User
    {
        $user = $request->user();
        abort_unless($user instanceof User && ($user->canAccessStaffWorkspace() || $user->isHospitalUser()), 403);

        return $user;
    }

    private function verifyPassword(User $user, string $password): void
    {
        if (! Hash::check($password, (string) $user->password)) {
            throw ValidationException::withMessages(['current_password' => __('bloodcare.two_factor.password_invalid')]);
        }
    }

    private function verifySecondFactor(User $user, string $code, TwoFactorAuthenticationService $twoFactor): void
    {
        if (! $twoFactor->verifyAndConsume($user, $code)) {
            throw ValidationException::withMessages(['code' => __('bloodcare.two_factor.invalid_code')]);
        }
    }

    private function requireEnabled(User $user): void
    {
        if (! $user->hasTwoFactorEnabled()) {
            abort(409, __('bloodcare.two_factor.not_enabled'));
        }
    }

    /** @return array{secret:string,issued_at:int}|null */
    private function pendingSetup(Request $request): ?array
    {
        $pending = $request->session()->get('two_factor.setup');
        if (! is_array($pending)
            || ! isset($pending['secret'], $pending['issued_at'])
            || time() - (int) $pending['issued_at'] > (int) config('bloodcare.two_factor.setup_ttl', 600)) {
            $request->session()->forget('two_factor.setup');

            return null;
        }

        return ['secret' => (string) $pending['secret'], 'issued_at' => (int) $pending['issued_at']];
    }

    private function backUrl(User $user): string
    {
        if ($user->isHospitalUser()) {
            return route('hospital.dashboard');
        }

        return route('backpack.account.info');
    }

    private function record(User $user, string $action, string $result): void
    {
        ActivityLog::record([
            'type' => 'Security',
            'action' => $action,
            'subject_type' => User::class,
            'subject_id' => $user->id,
            'user_id' => $user->id,
            'result' => $result,
            'details' => $user->email,
            'source' => 'two-factor-security',
        ]);
    }
}
