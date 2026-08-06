<?php

namespace App\Http\Controllers\Api;

use App\Actions\ResolveSocialUser;
use App\Concerns\IssuesApiTokens;
use App\Http\Controllers\Controller;
use App\Services\MobileAuthHandoff;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Laravel\Socialite\Facades\Socialite;

/**
 * OAuth for the mobile app.
 *
 * The app opens these routes in a system browser tab, so the flow has to be
 * stateless — there is no session to carry the OAuth state. The callback hands
 * control back over the app's custom scheme with a single-use code rather than
 * the token itself, keeping the bearer token out of URLs, browser history, and
 * any proxy in between.
 */
class SocialAuthController extends Controller
{
    use IssuesApiTokens;

    public function __construct(
        private readonly MobileAuthHandoff $handoff,
    ) {}

    public function redirect(string $provider): RedirectResponse
    {
        $this->ensureSupported($provider);

        return Socialite::driver($provider)
            ->stateless()
            ->redirectUrl($this->callbackUrl($provider))
            ->redirect();
    }

    public function callback(string $provider, ResolveSocialUser $resolveSocialUser): RedirectResponse
    {
        $this->ensureSupported($provider);

        try {
            $socialUser = Socialite::driver($provider)
                ->stateless()
                ->redirectUrl($this->callbackUrl($provider))
                ->user();
        } catch (\Exception) {
            return redirect()->away($this->appCallbackUrl(['error' => 'social_failed']));
        }

        $user = $resolveSocialUser($socialUser);

        return redirect()->away($this->appCallbackUrl([
            'code' => $this->handoff->issueSocialCode($user),
        ]));
    }

    public function exchange(Request $request): JsonResponse
    {
        $request->validate(['code' => ['required', 'string']]);

        $user = $this->handoff->consumeSocialCode($request->string('code')->value());

        if (! $user) {
            throw ValidationException::withMessages([
                'code' => ['This sign-in attempt has expired. Please try again.'],
            ]);
        }

        return response()->json($this->tokenResponse($user));
    }

    private function ensureSupported(string $provider): void
    {
        abort_unless(in_array($provider, ['google', 'facebook']), 404);
    }

    /**
     * Must match the redirect URI registered with the provider — it is a
     * different URI from the web app's, because it returns a code instead of
     * logging a session in.
     */
    private function callbackUrl(string $provider): string
    {
        return route('api.social.callback', ['provider' => $provider]);
    }

    /**
     * @param  array<string, string>  $query
     */
    private function appCallbackUrl(array $query): string
    {
        return config('services.mobile.oauth_callback').'?'.http_build_query($query);
    }
}
