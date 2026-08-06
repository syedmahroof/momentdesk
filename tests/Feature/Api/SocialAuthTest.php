<?php

use App\Models\User;
use Laravel\Socialite\Contracts\User as SocialiteUser;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\AbstractProvider;

function fakeMobileProvider(string $email = 'social@example.com'): void
{
    $socialUser = Mockery::mock(SocialiteUser::class);
    $socialUser->shouldReceive('getEmail')->andReturn($email);
    $socialUser->shouldReceive('getName')->andReturn('Social User');

    $provider = Mockery::mock(AbstractProvider::class);
    $provider->shouldReceive('stateless')->andReturnSelf();
    $provider->shouldReceive('redirectUrl')->andReturnSelf();
    $provider->shouldReceive('user')->andReturn($socialUser);

    Socialite::shouldReceive('driver')->with('google')->andReturn($provider);
}

function codeFromCallback(string $location): string
{
    parse_str(parse_url($location, PHP_URL_QUERY) ?: '', $query);

    return $query['code'] ?? '';
}

test('the mobile callback hands a single-use code back over the app scheme', function () {
    fakeMobileProvider();

    $response = $this->get('/api/auth/google/callback?code=oauth-code');

    $location = $response->headers->get('Location');

    expect($location)->toStartWith('momentdesk://auth/callback?');
    expect(codeFromCallback($location))->not->toBe('');

    $user = User::query()->firstWhere('email', 'social@example.com');

    expect($user)->not->toBeNull();
    expect($user?->tenant_id)->not->toBeNull();
    expect($user?->hasVerifiedEmail())->toBeTrue();
});

test('the app exchanges the code for an api token', function () {
    fakeMobileProvider();

    $code = codeFromCallback(
        $this->get('/api/auth/google/callback?code=oauth-code')->headers->get('Location')
    );

    $response = $this->postJson('/api/auth/social/exchange', ['code' => $code]);

    $response->assertOk()
        ->assertJsonStructure(['token', 'user'])
        ->assertJsonPath('user.email', 'social@example.com');

    $this->withToken($response->json('token'))
        ->getJson('/api/me')
        ->assertOk()
        ->assertJsonPath('user.email', 'social@example.com');
});

test('a code can only be exchanged once', function () {
    fakeMobileProvider();

    $code = codeFromCallback(
        $this->get('/api/auth/google/callback?code=oauth-code')->headers->get('Location')
    );

    $this->postJson('/api/auth/social/exchange', ['code' => $code])->assertOk();

    $this->postJson('/api/auth/social/exchange', ['code' => $code])
        ->assertStatus(422)
        ->assertJsonValidationErrors('code');
});

test('an unknown code is rejected', function () {
    $this->postJson('/api/auth/social/exchange', ['code' => 'made-up'])
        ->assertStatus(422)
        ->assertJsonValidationErrors('code');
});

test('a failed provider handshake sends the app an error instead of a code', function () {
    $provider = Mockery::mock(AbstractProvider::class);
    $provider->shouldReceive('stateless')->andReturnSelf();
    $provider->shouldReceive('redirectUrl')->andReturnSelf();
    $provider->shouldReceive('user')->andThrow(new RuntimeException('denied'));

    Socialite::shouldReceive('driver')->with('google')->andReturn($provider);

    $response = $this->get('/api/auth/google/callback');

    expect($response->headers->get('Location'))->toBe('momentdesk://auth/callback?error=social_failed');
});

test('unsupported providers are not routable', function () {
    $this->get('/api/auth/linkedin/redirect')->assertNotFound();
});
