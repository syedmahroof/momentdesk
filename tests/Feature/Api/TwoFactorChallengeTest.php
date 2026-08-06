<?php

use App\Models\User;
use Laravel\Fortify\Contracts\TwoFactorAuthenticationProvider;

test('login returns a challenge instead of a token when two factor is enabled', function () {
    $user = User::factory()->withTwoFactor()->create();

    $response = $this->postJson('/api/login', [
        'email' => $user->email,
        'password' => 'password',
    ]);

    $response->assertOk()
        ->assertJsonPath('two_factor', true)
        ->assertJsonStructure(['challenge_token'])
        ->assertJsonMissingPath('token');
});

test('login still returns a token when two factor is not enabled', function () {
    $user = User::factory()->create();

    $this->postJson('/api/login', [
        'email' => $user->email,
        'password' => 'password',
    ])->assertOk()->assertJsonStructure(['token', 'user']);
});

test('a valid authenticator code completes the login', function () {
    $user = User::factory()->withTwoFactor()->create();

    $this->mock(TwoFactorAuthenticationProvider::class)
        ->shouldReceive('verify')
        ->once()
        ->with('secret', '123456')
        ->andReturnTrue();

    $challengeToken = $this->postJson('/api/login', [
        'email' => $user->email,
        'password' => 'password',
    ])->json('challenge_token');

    $this->postJson('/api/two-factor-challenge', [
        'challenge_token' => $challengeToken,
        'code' => '123456',
    ])->assertOk()->assertJsonStructure(['token', 'user'])
        ->assertJsonPath('user.two_factor_enabled', true);
});

test('an invalid authenticator code is rejected', function () {
    $user = User::factory()->withTwoFactor()->create();

    $this->mock(TwoFactorAuthenticationProvider::class)
        ->shouldReceive('verify')
        ->andReturnFalse();

    $challengeToken = $this->postJson('/api/login', [
        'email' => $user->email,
        'password' => 'password',
    ])->json('challenge_token');

    $this->postJson('/api/two-factor-challenge', [
        'challenge_token' => $challengeToken,
        'code' => '000000',
    ])->assertStatus(422)->assertJsonValidationErrors('code');
});

test('a recovery code completes the login and is burned', function () {
    $user = User::factory()->withTwoFactor()->create();

    $challengeToken = $this->postJson('/api/login', [
        'email' => $user->email,
        'password' => 'password',
    ])->json('challenge_token');

    $this->postJson('/api/two-factor-challenge', [
        'challenge_token' => $challengeToken,
        'recovery_code' => 'recovery-code-1',
    ])->assertOk()->assertJsonStructure(['token']);

    $remaining = json_decode(decrypt($user->fresh()->two_factor_recovery_codes), true);

    expect($remaining)->not->toContain('recovery-code-1');
});

test('a challenge token cannot be reused', function () {
    $user = User::factory()->withTwoFactor()->create();

    $challengeToken = $this->postJson('/api/login', [
        'email' => $user->email,
        'password' => 'password',
    ])->json('challenge_token');

    $this->postJson('/api/two-factor-challenge', [
        'challenge_token' => $challengeToken,
        'recovery_code' => 'recovery-code-1',
    ])->assertOk();

    $this->postJson('/api/two-factor-challenge', [
        'challenge_token' => $challengeToken,
        'recovery_code' => 'recovery-code-1',
    ])->assertStatus(422)->assertJsonValidationErrors('challenge_token');
});

test('an unknown challenge token is rejected', function () {
    $this->postJson('/api/two-factor-challenge', [
        'challenge_token' => 'made-up',
        'code' => '123456',
    ])->assertStatus(422)->assertJsonValidationErrors('challenge_token');
});
