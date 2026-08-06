<?php

namespace App\Http\Controllers\Api;

use App\Concerns\IssuesApiTokens;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\MobileAuthHandoff;
use Illuminate\Auth\Events\Registered;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Laravel\Fortify\Contracts\CreatesNewUsers;
use Laravel\Fortify\Contracts\TwoFactorAuthenticationProvider;

class AuthController extends Controller
{
    use IssuesApiTokens;

    public function __construct(
        private readonly MobileAuthHandoff $handoff,
    ) {}

    /**
     * Registration reuses the same action Fortify runs for the web app, so
     * password rules and tenant provisioning stay in one place.
     */
    public function register(Request $request, CreatesNewUsers $creator): JsonResponse
    {
        $user = $creator->create($request->all());

        event(new Registered($user));

        // `Registered` only mails a verification link once the User model
        // implements MustVerifyEmail, which it does not yet — the web app
        // never asks anyone to verify. Until that changes, the mobile flow
        // sends the link itself; the guard keeps it from double-sending if
        // the contract is added later.
        if (! $user instanceof MustVerifyEmail && ! $user->hasVerifiedEmail()) {
            $user->sendEmailVerificationNotification();
        }

        return response()->json($this->tokenResponse($user), 201);
    }

    public function login(Request $request): JsonResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $user = User::query()->where('email', $credentials['email'])->first();

        if (! $user || ! Hash::check($credentials['password'], $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['These credentials do not match our records.'],
            ]);
        }

        // No token until the second factor is satisfied — otherwise the mobile
        // client would be a way around the 2FA the user turned on for the web.
        if ($user->hasEnabledTwoFactorAuthentication()) {
            return response()->json([
                'two_factor' => true,
                'challenge_token' => $this->handoff->issueTwoFactorChallenge($user),
            ]);
        }

        return response()->json($this->tokenResponse($user));
    }

    public function twoFactorChallenge(Request $request, TwoFactorAuthenticationProvider $provider): JsonResponse
    {
        $request->validate([
            'challenge_token' => ['required', 'string'],
            'code' => ['nullable', 'string'],
            'recovery_code' => ['nullable', 'string'],
        ]);

        $challengeToken = $request->string('challenge_token')->value();
        $user = $this->handoff->userForTwoFactorChallenge($challengeToken);

        if (! $user) {
            throw ValidationException::withMessages([
                'challenge_token' => ['This login attempt has expired. Please sign in again.'],
            ]);
        }

        $code = trim($request->string('code')->value());
        $recoveryCode = trim($request->string('recovery_code')->value());

        $passed = ($code !== '' && $provider->verify(decrypt($user->two_factor_secret), $code))
            || ($recoveryCode !== '' && $this->consumeRecoveryCode($user, $recoveryCode));

        if (! $passed) {
            throw ValidationException::withMessages([
                'code' => ['The provided two factor authentication code was invalid.'],
            ]);
        }

        $this->handoff->forgetTwoFactorChallenge($challengeToken);

        return response()->json($this->tokenResponse($user));
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Logged out.']);
    }

    public function me(Request $request): JsonResponse
    {
        return response()->json([
            'user' => $this->serializeUser($request->user()),
        ]);
    }

    /**
     * Burns a valid recovery code so it cannot be replayed.
     */
    private function consumeRecoveryCode(User $user, string $recoveryCode): bool
    {
        $codes = json_decode(decrypt($user->two_factor_recovery_codes), true) ?: [];

        if (! in_array($recoveryCode, $codes, true)) {
            return false;
        }

        $user->replaceRecoveryCode($recoveryCode);

        return true;
    }
}
