<?php

use App\Models\User;
use Illuminate\Auth\Middleware\EnsureEmailIsVerified;
use Laravel\Socialite\Contracts\Provider;
use Laravel\Socialite\Contracts\User as SocialiteUser;
use Laravel\Socialite\Facades\Socialite;

test('social signup provisions a tenant before redirecting to the dashboard', function () {
    $socialUser = \Mockery::mock(SocialiteUser::class);
    $socialUser->shouldReceive('getEmail')->once()->andReturn('social@example.com');
    $socialUser->shouldReceive('getName')->once()->andReturn('Social User');

    $provider = \Mockery::mock(Provider::class);
    $provider->shouldReceive('user')->once()->andReturn($socialUser);

    Socialite::shouldReceive('driver')
        ->once()
        ->with('google')
        ->andReturn($provider);

    $response = $this->get(route('social.callback', ['provider' => 'google']));

    $this->assertAuthenticated();

    $user = User::query()->firstWhere('email', 'social@example.com');

    expect($user)->not->toBeNull();
    expect($user?->tenant_id)->not->toBeNull();
    expect($user?->role)->toBe('admin');

    $response->assertRedirect(route('dashboard'));

    $this->withoutMiddleware(EnsureEmailIsVerified::class)
        ->actingAs($user)
        ->get(route('tenant-profile.edit'))
        ->assertOk();
});
