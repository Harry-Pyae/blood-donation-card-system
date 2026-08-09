<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\View;

class CheckIfAdmin
{
    public function handle(Request $request, Closure $next)
    {
        $guardName = (string) config('backpack.base.guard', 'web');
        Auth::shouldUse($guardName);
        $guard = Auth::guard($guardName);

        if ($guard->guest()) {
            return $this->respondToGuestRequest($request);
        }

        $user = $guard->user();
        if (! $user instanceof User || ! $user->canAccessStaffWorkspace()) {
            return $this->respondToDeniedAccount($request, $user, $guardName);
        }

        if ($user->isLaboratoryUser()) {
            $prefix = trim((string) config('backpack.base.route_prefix', 'admin'), '/');
            $allowed = $request->is($prefix.'/lab*')
                || $request->is($prefix.'/logout')
                || $request->is($prefix.'/edit-account-info*')
                || $request->is($prefix.'/change-password*');
            abort_unless($allowed, 403, 'Laboratory accounts are restricted to the Laboratory workspace.');
        }

        $request->setUserResolver(static fn () => $user);

        // The request has already passed BloodCare authorization. Share this
        // exact model with Backpack/Tabler views so rendering remains stable
        // throughout the Blade lifecycle.
        View::share('bloodcareWorkspaceUser', $user);

        return $next($request);
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

    private function respondToGuestRequest(Request $request)
    {
        if ($request->ajax() || $request->wantsJson()) {
            return response(trans('backpack::base.unauthorized'), 401);
        }

        return redirect()->route('backpack.auth.login');
    }

    private function respondToDeniedAccount(Request $request, ?User $user, string $guardName)
    {
        $message = $this->denialMessage($user);
        Auth::guard($guardName)->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json(['message' => $message], 403);
        }

        return redirect()
            ->route('backpack.auth.login')
            ->withErrors([config('backpack.base.authentication_column', 'email') => $message]);
    }
}
