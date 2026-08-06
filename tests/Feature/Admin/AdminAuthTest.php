<?php

use App\Models\Admin;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

test('the admin login screen can be rendered', function (): void {
    $this->get(route('admin.login'))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page->component('Admin/Auth/Login'));
});

test('admins can authenticate on the admin guard', function (): void {
    $admin = Admin::factory()->create(['password' => Hash::make('password')]);

    $this->post(route('admin.login.store'), [
        'email' => $admin->email,
        'password' => 'password',
    ])->assertRedirect(route('admin.dashboard'));

    $this->assertAuthenticatedAs($admin, 'admin');
    $this->assertGuest('web');
});

test('a tenant intended url does not hijack the admin login redirect', function (): void {
    $admin = Admin::factory()->create(['password' => Hash::make('password')]);

    // A guest hitting a tenant page stores `url.intended` in the shared session.
    $this->get(route('dashboard'))->assertRedirect(route('login'));

    $this->post(route('admin.login.store'), [
        'email' => $admin->email,
        'password' => 'password',
    ])->assertRedirect(route('admin.dashboard'));
});

test('an intended url inside the admin panel is honoured', function (): void {
    $admin = Admin::factory()->create(['password' => Hash::make('password')]);

    $this->get(route('admin.tenants.index'))->assertRedirect(route('admin.login'));

    $this->post(route('admin.login.store'), [
        'email' => $admin->email,
        'password' => 'password',
    ])->assertRedirect(route('admin.tenants.index'));
});

test('admins cannot authenticate with an invalid password', function (): void {
    $admin = Admin::factory()->create();

    $this->post(route('admin.login.store'), [
        'email' => $admin->email,
        'password' => 'wrong-password',
    ])->assertSessionHasErrors('email');

    $this->assertGuest('admin');
});

test('tenant user credentials are rejected by the admin guard', function (): void {
    $tenant = Tenant::factory()->create();
    $user = User::factory()->create([
        'tenant_id' => $tenant->id,
        'password' => Hash::make('password'),
    ]);

    $this->post(route('admin.login.store'), [
        'email' => $user->email,
        'password' => 'password',
    ])->assertSessionHasErrors('email');

    $this->assertGuest('admin');
});

test('admin credentials are rejected by the tenant login', function (): void {
    $admin = Admin::factory()->create(['password' => Hash::make('password')]);

    $this->post(route('login.store'), [
        'email' => $admin->email,
        'password' => 'password',
    ])->assertSessionHasErrors('email');

    $this->assertGuest('web');
});

test('an authenticated tenant user has no access to the admin panel', function (): void {
    $tenant = Tenant::factory()->create();
    $user = User::factory()->create(['tenant_id' => $tenant->id, 'role' => 'admin']);

    $this->actingAs($user)->get(route('admin.dashboard'))->assertRedirect(route('admin.login'));
    $this->actingAs($user)->get(route('admin.tenants.index'))->assertRedirect(route('admin.login'));
});

test('an authenticated admin has no access to the tenant app', function (): void {
    $admin = Admin::factory()->create(['password' => Hash::make('password')]);

    $this->post(route('admin.login.store'), ['email' => $admin->email, 'password' => 'password']);
    $this->assertAuthenticatedAs($admin, 'admin');

    $this->get(route('dashboard'))->assertRedirect(route('login'));
});

test('admins can log out', function (): void {
    $admin = Admin::factory()->create(['password' => Hash::make('password')]);

    $this->post(route('admin.login.store'), ['email' => $admin->email, 'password' => 'password']);
    $this->assertAuthenticatedAs($admin, 'admin');

    $this->post(route('admin.logout'))->assertRedirect(route('admin.login'));

    $this->assertGuest('admin');
});

test('the admin login is rate limited', function (): void {
    $admin = Admin::factory()->create();

    foreach (range(1, 5) as $attempt) {
        $this->post(route('admin.login.store'), [
            'email' => $admin->email,
            'password' => 'wrong-password',
        ]);
    }

    $this->post(route('admin.login.store'), [
        'email' => $admin->email,
        'password' => 'wrong-password',
    ])->assertTooManyRequests();
});
