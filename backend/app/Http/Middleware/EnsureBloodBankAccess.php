<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class EnsureBloodBankAccess
{
    public function handle(Request $request, Closure $next)
    {
        abort_unless($request->user()?->canManageBloodBank(), 403);
        return $next($request);
    }
}
