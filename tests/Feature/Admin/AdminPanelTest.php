<?php

use App\Models\Admin;
use App\Models\Customer;
use App\Models\Tenant;
use App\Models\User;

beforeEach(function (): void {
    $this->superAdmin = Admin::factory()->create();
});

test('guests are redirected to the admin login', function (): void {
    $this->get(route('admin.dashboard'))->assertRedirect(route('admin.login'));
    $this->get(route('admin.tenants.index'))->assertRedirect(route('admin.login'));
});

test('super admins see platform wide stats on the admin dashboard', function (): void {
    $tenants = Tenant::factory()->count(2)->create();
    Tenant::factory()->create(['status' => 'suspended']);
    Customer::factory()->for($tenants->first())->count(3)->create();

    $this->actingAs($this->superAdmin, 'admin')
        ->get(route('admin.dashboard'))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('Admin/Dashboard')
            ->where('stats.total_tenants', 3)
            ->where('stats.active_tenants', 2)
            ->where('stats.suspended_tenants', 1)
            ->where('stats.total_customers', 3)
            ->has('recentTenants', 3)
            ->has('tenantSignups', 6)
        );
});

test('super admins can list tenants', function (): void {
    Tenant::factory()->create(['name' => 'Acme Jewellers']);

    $this->actingAs($this->superAdmin, 'admin')
        ->get(route('admin.tenants.index'))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('Admin/Tenants/Index')
            ->has('tenants.data', 1)
            ->where('tenants.data.0.name', 'Acme Jewellers')
            ->where('tenants.data.0.users_count', 0)
        );
});

test('the tenant list can be filtered by search and status', function (): void {
    Tenant::factory()->create(['name' => 'Acme Jewellers']);
    Tenant::factory()->create(['name' => 'Globex Gold', 'status' => 'suspended']);

    $this->actingAs($this->superAdmin, 'admin')
        ->get(route('admin.tenants.index', ['search' => 'Globex']))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->has('tenants.data', 1)
            ->where('tenants.data.0.name', 'Globex Gold')
        );

    $this->actingAs($this->superAdmin, 'admin')
        ->get(route('admin.tenants.index', ['status' => 'active']))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->has('tenants.data', 1)
            ->where('tenants.data.0.name', 'Acme Jewellers')
        );
});

test('the tenants list carries each tenant with its users, so the drawer needs no extra request', function (): void {
    $tenant = Tenant::factory()->create();
    User::factory()->create(['tenant_id' => $tenant->id, 'name' => 'Tenant Admin', 'role' => 'admin']);

    $this->actingAs($this->superAdmin, 'admin')
        ->get(route('admin.tenants.index'))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('Admin/Tenants/Index')
            ->where('tenants.data.0.id', $tenant->id)
            ->has('tenants.data.0.users', 1)
            ->where('tenants.data.0.users.0.name', 'Tenant Admin')
            ->where('tenants.data.0.users.0.role', 'admin')
        );
});
