<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureTenant
{
    public function handle(Request $request, Closure $next): Response
    {
        if (auth()->check() && ! auth()->user()->isSuperAdmin() && ! auth()->user()->tenant_id) {
            abort(403, 'No tenant assigned. Please contact your administrator.');
        }

        return $next($request);
    }
}
