<?php

namespace App\Http\Middleware;

use App\Actions\ProvisionTenantForUser;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureTenant
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && ! $user->tenant_id) {
            $user = app(ProvisionTenantForUser::class)($user);
            $request->setUserResolver(static fn () => $user);
        }

        if ($user && ! $user->tenant_id) {
            abort(403, 'No tenant assigned. Please contact your administrator.');
        }

        return $next($request);
    }
}
