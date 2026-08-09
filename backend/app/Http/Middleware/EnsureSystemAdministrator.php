<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureSystemAdministrator
{
    /**
     * Keep account roles and bans exclusive to System Administrators.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = backpack_user();

        abort_unless(
            $user && method_exists($user, 'canManageUsers') && $user->canManageUsers(),
            403,
        );

        return $next($request);
    }
}
