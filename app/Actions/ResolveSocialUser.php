<?php

namespace App\Actions;

use App\Models\User;
use Illuminate\Support\Str;
use Laravel\Socialite\Contracts\User as SocialiteUser;

/**
 * Maps a provider profile onto a local account, shared by the web session
 * flow and the mobile token flow so both create identical users.
 */
class ResolveSocialUser
{
    public function __construct(
        private readonly ProvisionTenantForUser $provisionTenantForUser,
    ) {}

    public function __invoke(SocialiteUser $socialUser): User
    {
        $user = User::firstOrCreate(
            ['email' => $socialUser->getEmail()],
            [
                'name' => $socialUser->getName() ?? $socialUser->getNickname() ?? 'User',
                'password' => bcrypt(Str::random(32)),
                'role' => 'admin',
            ]
        );

        // The provider has already proven the address belongs to them.
        // `email_verified_at` isn't fillable, so this can't ride along with
        // the attributes above.
        if (! $user->hasVerifiedEmail()) {
            $user->markEmailAsVerified();
        }

        return ($this->provisionTenantForUser)($user);
    }
}
