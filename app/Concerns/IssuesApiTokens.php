<?php

namespace App\Concerns;

use App\Models\User;

trait IssuesApiTokens
{
    /**
     * The payload every successful mobile authentication returns.
     *
     * @return array<string, mixed>
     */
    protected function tokenResponse(User $user): array
    {
        return [
            'token' => $user->createToken('mobile')->plainTextToken,
            'user' => $this->serializeUser($user),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function serializeUser(User $user): array
    {
        $user->loadMissing('tenant');

        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'role' => $user->role,
            'email_verified' => $user->hasVerifiedEmail(),
            'two_factor_enabled' => $user->hasEnabledTwoFactorAuthentication(),
            'tenant' => $user->tenant ? [
                'id' => $user->tenant->id,
                'name' => $user->tenant->name,
            ] : null,
        ];
    }
}
