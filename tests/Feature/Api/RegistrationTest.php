<?php

use App\Models\User;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Support\Facades\Notification;

test('a new user can register from the mobile app', function () {
    Notification::fake();

    $response = $this->postJson('/api/register', [
        'name' => 'Mobile User',
        'email' => 'mobile@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    $response->assertCreated()
        ->assertJsonStructure(['token', 'user' => ['id', 'name', 'email', 'tenant']])
        ->assertJsonPath('user.email', 'mobile@example.com')
        ->assertJsonPath('user.email_verified', false)
        ->assertJsonPath('user.two_factor_enabled', false);

    $user = User::query()->firstWhere('email', 'mobile@example.com');

    expect($user)->not->toBeNull();
    expect($user?->tenant_id)->not->toBeNull();
    expect($user?->role)->toBe('admin');

    Notification::assertSentTo($user, VerifyEmail::class);
});

test('the token returned by registration works against the api', function () {
    $response = $this->postJson('/api/register', [
        'name' => 'Mobile User',
        'email' => 'mobile@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    $this->withToken($response->json('token'))
        ->getJson('/api/me')
        ->assertOk()
        ->assertJsonPath('user.email', 'mobile@example.com');
});

test('registration rejects an email that is already taken', function () {
    User::factory()->create(['email' => 'taken@example.com']);

    $this->postJson('/api/register', [
        'name' => 'Mobile User',
        'email' => 'taken@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ])->assertStatus(422)->assertJsonValidationErrors('email');
});

test('registration requires a confirmed password', function () {
    $this->postJson('/api/register', [
        'name' => 'Mobile User',
        'email' => 'mobile@example.com',
        'password' => 'password',
        'password_confirmation' => 'different',
    ])->assertStatus(422)->assertJsonValidationErrors('password');
});
