<?php

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;

test('a reset link can be requested from the mobile app', function () {
    Notification::fake();

    $user = User::factory()->create();

    $this->postJson('/api/forgot-password', ['email' => $user->email])->assertOk();

    Notification::assertSentTo($user, ResetPassword::class);
});

test('an unknown email gets the same answer as a known one', function () {
    Notification::fake();

    $known = $this->postJson('/api/forgot-password', ['email' => User::factory()->create()->email]);
    $unknown = $this->postJson('/api/forgot-password', ['email' => 'nobody@example.com']);

    $unknown->assertOk();
    expect($unknown->json('message'))->toBe($known->json('message'));
});

test('a password can be reset with a valid token', function () {
    Notification::fake();

    $user = User::factory()->create();

    $this->postJson('/api/forgot-password', ['email' => $user->email]);

    Notification::assertSentTo($user, ResetPassword::class, function (ResetPassword $notification) use ($user) {
        $this->postJson('/api/reset-password', [
            'token' => $notification->token,
            'email' => $user->email,
            'password' => 'new-password',
            'password_confirmation' => 'new-password',
        ])->assertOk();

        return true;
    });

    expect(Hash::check('new-password', $user->fresh()->password))->toBeTrue();
});

test('a password cannot be reset with an invalid token', function () {
    $user = User::factory()->create();

    $this->postJson('/api/reset-password', [
        'token' => 'not-a-real-token',
        'email' => $user->email,
        'password' => 'new-password',
        'password_confirmation' => 'new-password',
    ])->assertStatus(422)->assertJsonValidationErrors('email');

    expect(Hash::check('new-password', $user->fresh()->password))->toBeFalse();
});

test('resetting requires a confirmed password', function () {
    $this->postJson('/api/reset-password', [
        'token' => 'token',
        'email' => 'someone@example.com',
        'password' => 'new-password',
        'password_confirmation' => 'mismatch',
    ])->assertStatus(422)->assertJsonValidationErrors('password');
});
