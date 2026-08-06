<?php

use App\Models\User;
use Illuminate\Support\Facades\Hash;

test('a user can update their name and email', function () {
    $user = User::factory()->create();

    $this->actingAs($user, 'sanctum')
        ->patchJson('/api/profile', [
            'name' => 'Renamed User',
            'email' => 'renamed@example.com',
        ])
        ->assertOk()
        ->assertJsonPath('user.name', 'Renamed User')
        ->assertJsonPath('user.email', 'renamed@example.com');

    expect($user->fresh()->name)->toBe('Renamed User');
});

test('changing the email drops the verified state', function () {
    $user = User::factory()->create();

    expect($user->hasVerifiedEmail())->toBeTrue();

    $this->actingAs($user, 'sanctum')
        ->patchJson('/api/profile', [
            'name' => $user->name,
            'email' => 'moved@example.com',
        ])
        ->assertOk()
        ->assertJsonPath('user.email_verified', false);

    expect($user->fresh()->hasVerifiedEmail())->toBeFalse();
});

test('keeping the same email leaves it verified', function () {
    $user = User::factory()->create();

    $this->actingAs($user, 'sanctum')
        ->patchJson('/api/profile', [
            'name' => 'Renamed User',
            'email' => $user->email,
        ])
        ->assertOk()
        ->assertJsonPath('user.email_verified', true);
});

test('an email already used by someone else is rejected', function () {
    $user = User::factory()->create();
    $other = User::factory()->create();

    $this->actingAs($user, 'sanctum')
        ->patchJson('/api/profile', [
            'name' => $user->name,
            'email' => $other->email,
        ])
        ->assertStatus(422)
        ->assertJsonValidationErrors('email');
});

test('an account can be deleted with the right password', function () {
    $user = User::factory()->create();
    $user->createToken('mobile');

    $this->actingAs($user, 'sanctum')
        ->deleteJson('/api/profile', ['password' => 'password'])
        ->assertOk();

    expect(User::query()->find($user->id))->toBeNull();
    expect($user->tokens()->count())->toBe(0);
});

test('account deletion needs the current password', function () {
    $user = User::factory()->create();

    $this->actingAs($user, 'sanctum')
        ->deleteJson('/api/profile', ['password' => 'not-the-password'])
        ->assertStatus(422)
        ->assertJsonValidationErrors('password');

    expect(User::query()->find($user->id))->not->toBeNull();
});

test('a password can be changed with the current one', function () {
    $user = User::factory()->create();

    $this->actingAs($user, 'sanctum')
        ->putJson('/api/password', [
            'current_password' => 'password',
            'password' => 'new-password',
            'password_confirmation' => 'new-password',
        ])
        ->assertOk();

    expect(Hash::check('new-password', $user->fresh()->password))->toBeTrue();
});

test('a password change is rejected without the current password', function () {
    $user = User::factory()->create();

    $this->actingAs($user, 'sanctum')
        ->putJson('/api/password', [
            'current_password' => 'wrong-password',
            'password' => 'new-password',
            'password_confirmation' => 'new-password',
        ])
        ->assertStatus(422)
        ->assertJsonValidationErrors('current_password');

    expect(Hash::check('password', $user->fresh()->password))->toBeTrue();
});

test('profile endpoints require authentication', function () {
    $this->patchJson('/api/profile', ['name' => 'A', 'email' => 'a@example.com'])->assertUnauthorized();
    $this->putJson('/api/password', [])->assertUnauthorized();
    $this->deleteJson('/api/profile', [])->assertUnauthorized();
});
