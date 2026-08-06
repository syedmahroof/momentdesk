<?php

namespace App\Http\Controllers\Auth;

use App\Actions\ResolveSocialUser;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;

class SocialAuthController extends Controller
{
    public function __construct(
        private readonly ResolveSocialUser $resolveSocialUser,
    ) {}

    /** Redirect to the OAuth provider */
    public function redirect(string $provider)
    {
        $this->validateProvider($provider);

        return Socialite::driver($provider)->redirect();
    }

    /** Handle the provider callback */
    public function callback(string $provider)
    {
        $this->validateProvider($provider);

        try {
            $socialUser = Socialite::driver($provider)->user();
        } catch (\Exception $e) {
            return redirect()->route('login')->withErrors(['social' => 'Social login failed. Please try again.']);
        }

        $user = ($this->resolveSocialUser)($socialUser);

        Auth::login($user, true);

        return redirect()->intended(route('dashboard'));
    }

    private function validateProvider(string $provider): void
    {
        abort_unless(in_array($provider, ['google', 'facebook']), 404);
    }
}
