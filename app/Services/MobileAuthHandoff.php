<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

/**
 * Short-lived, single-use handoff tokens for the mobile app.
 *
 * The web app carries a half-finished login in the session; a token-based API
 * client has no session, so the two places where a login is momentarily
 * incomplete — waiting on a second factor, and bouncing through an OAuth
 * browser tab — park the user id in the cache instead.
 */
class MobileAuthHandoff
{
    /**
     * Long enough to open an authenticator app, short enough that a leaked
     * challenge token is worthless by the time anyone finds it.
     */
    private const TWO_FACTOR_TTL_SECONDS = 300;

    /**
     * The app exchanges this the moment the browser tab hands control back,
     * so it never needs to survive longer than a round trip.
     */
    private const SOCIAL_TTL_SECONDS = 60;

    public function issueTwoFactorChallenge(User $user): string
    {
        return $this->issue('two-factor', $user, self::TWO_FACTOR_TTL_SECONDS);
    }

    public function userForTwoFactorChallenge(string $token): ?User
    {
        return $this->resolve('two-factor', $token);
    }

    public function forgetTwoFactorChallenge(string $token): void
    {
        Cache::forget($this->key('two-factor', $token));
    }

    public function issueSocialCode(User $user): string
    {
        return $this->issue('social', $user, self::SOCIAL_TTL_SECONDS);
    }

    /**
     * Resolves and immediately burns the code, so a callback URL that leaks
     * into browser history or a proxy log cannot be replayed.
     */
    public function consumeSocialCode(string $code): ?User
    {
        $user = $this->resolve('social', $code);

        Cache::forget($this->key('social', $code));

        return $user;
    }

    private function issue(string $purpose, User $user, int $ttlSeconds): string
    {
        $token = Str::random(64);

        Cache::put($this->key($purpose, $token), $user->getKey(), $ttlSeconds);

        return $token;
    }

    private function resolve(string $purpose, string $token): ?User
    {
        $userId = Cache::get($this->key($purpose, $token));

        return $userId ? User::query()->find($userId) : null;
    }

    private function key(string $purpose, string $token): string
    {
        return "mobile-auth:{$purpose}:".hash('sha256', $token);
    }
}
