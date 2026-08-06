<?php

use App\Models\User;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Support\Facades\Notification;

test('an unverified user can request another verification email', function () {
    Notification::fake();

    $user = User::factory()->unverified()->create();

    $this->actingAs($user, 'sanctum')
        ->postJson('/api/email/verification-notification')
        ->assertOk()
        ->assertJsonPath('message', 'Verification link sent.');

    Notification::assertSentTo($user, VerifyEmail::class);
});

test('a verified user is told there is nothing to do', function () {
    Notification::fake();

    $user = User::factory()->create();

    $this->actingAs($user, 'sanctum')
        ->postJson('/api/email/verification-notification')
        ->assertOk()
        ->assertJsonPath('message', 'Your email address is already verified.');

    Notification::assertNothingSent();
});

test('me reports the verification state so the app can gate itself', function () {
    $user = User::factory()->unverified()->create();

    $this->actingAs($user, 'sanctum')
        ->getJson('/api/me')
        ->assertOk()
        ->assertJsonPath('user.email_verified', false);

    $user->markEmailAsVerified();

    $this->actingAs($user->fresh(), 'sanctum')
        ->getJson('/api/me')
        ->assertOk()
        ->assertJsonPath('user.email_verified', true);
});

test('verification requests require authentication', function () {
    $this->postJson('/api/email/verification-notification')->assertUnauthorized();
});
