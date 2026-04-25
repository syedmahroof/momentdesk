<?php

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Auth\Middleware\EnsureEmailIsVerified;

test('registration screen can be rendered', function () {
    $response = $this->get(route('register'));

    $response->assertOk();
});

test('new users can register', function () {
    $response = $this->post(route('register.store'), [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    $this->assertAuthenticated();

    $user = User::query()->firstWhere('email', 'test@example.com');

    expect($user)->not->toBeNull();
    expect($user?->tenant_id)->not->toBeNull();
    expect($user?->role)->toBe('admin');

    $response->assertRedirect(route('dashboard', absolute: false));

    $this->withoutMiddleware(EnsureEmailIsVerified::class)
        ->actingAs($user)
        ->get(route('tenant-profile.edit'))
        ->assertOk();
});

test('tenant middleware provisions legacy tenantless users', function () {
    $user = User::factory()->create([
        'tenant_id' => null,
        'role' => 'admin',
    ]);

    $this->withoutMiddleware(EnsureEmailIsVerified::class)
        ->actingAs($user)
        ->get(route('tenant-profile.edit'))
        ->assertOk();

    expect($user->fresh()->tenant_id)->not->toBeNull();
});

test('tenant provisioning reuses an existing tenant with the same email', function () {
    $tenant = Tenant::create([
        'name' => 'Existing Organization',
        'slug' => 'existing-organization',
        'email' => 'existing@example.com',
        'status' => 'active',
    ]);

    $user = User::factory()->create([
        'email' => 'existing@example.com',
        'tenant_id' => null,
        'role' => 'admin',
    ]);

    $this->withoutMiddleware(EnsureEmailIsVerified::class)
        ->actingAs($user)
        ->get(route('tenant-profile.edit'))
        ->assertOk();

    expect($user->fresh()->tenant_id)->toBe($tenant->id);
    expect(Tenant::query()->where('email', 'existing@example.com')->count())->toBe(1);
});
