<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class EnsureLaboratoryAccess
{
    public function handle(Request $request, Closure $next)
    {
        abort_unless($request->user()?->canUseLaboratory(), 403);
        return $next($request);
    }
}
