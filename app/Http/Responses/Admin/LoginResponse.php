<?php

namespace App\Http\Responses\Admin;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Laravel\Fortify\Contracts\LoginResponse as LoginResponseContract;

/**
 * Sends an authenticated admin into the admin panel.
 *
 * Fortify's default response redirects to the session's intended URL, but
 * `url.intended` is shared with the tenant guard — a guest hitting a tenant
 * page before signing in here would otherwise bounce the admin straight back
 * out to the tenant login. Only intended URLs inside the panel are honoured.
 */
class LoginResponse implements LoginResponseContract
{
    public function toResponse($request): JsonResponse|RedirectResponse
    {
        $intended = $request->session()->pull('url.intended');

        return $request->wantsJson()
            ? new JsonResponse(['two_factor' => false])
            : redirect($this->targetFor($intended));
    }

    private function targetFor(?string $intended): string
    {
        if ($intended === null) {
            return route('admin.dashboard');
        }

        $path = parse_url($intended, PHP_URL_PATH) ?: '';

        return $path === '/admin' || str_starts_with($path, '/admin/')
            ? $intended
            : route('admin.dashboard');
    }
}
