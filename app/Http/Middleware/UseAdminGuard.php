<?php

namespace App\Http\Middleware;

use App\Http\Responses\Admin\LoginResponse as AdminLoginResponse;
use Closure;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Laravel\Fortify\Contracts\LoginResponse as LoginResponseContract;
use Laravel\Fortify\Contracts\LoginViewResponse;
use Laravel\Fortify\Http\Responses\SimpleViewResponse;
use Symfony\Component\HttpFoundation\Response;

/**
 * Points Fortify at the `admin` guard for the duration of the request.
 *
 * Fortify binds StatefulGuard as a non-shared binding that reads
 * `fortify.guard` when resolved, and resolves its redirects from
 * `fortify.redirects.*`, so swapping those values before the controller is
 * built is enough to run the whole Fortify login pipeline against `admin`.
 */
class UseAdminGuard
{
    public function handle(Request $request, Closure $next): Response
    {
        config([
            'fortify.guard' => 'admin',
            'fortify.redirects.login' => route('admin.dashboard'),
            'fortify.redirects.logout' => route('admin.login'),

            // Admins have no two-factor UI, and the challenge route belongs to
            // the web guard — keep the admin pipeline to credentials only.
            'fortify.features' => [],
        ]);

        app()->bind(LoginViewResponse::class, fn (): SimpleViewResponse => new SimpleViewResponse(
            fn (Request $request) => Inertia::render('Admin/Auth/Login', [
                'status' => $request->session()->get('status'),
            ])
        ));

        app()->bind(LoginResponseContract::class, AdminLoginResponse::class);

        return $next($request);
    }
}
