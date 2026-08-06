<?php

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

/**
 * The user factory leaves `tenant_id` null and relies on the ensure-tenant
 * middleware to fill it in mid-request, which is too late for tests that need
 * the tenant up front.
 *
 * @param  array<string, mixed>  $attributes
 */
function userWithTenant(array $attributes = []): User
{
    return User::factory()->create(['tenant_id' => Tenant::factory()->create($attributes)->id]);
}

test('the business profile can be read', function () {
    $user = userWithTenant(['phone' => '+91 98765 43210', 'address' => '12 Market Road']);

    $this->actingAs($user, 'sanctum')
        ->getJson('/api/tenant-profile')
        ->assertOk()
        ->assertJsonPath('tenant.name', $user->tenant->name)
        ->assertJsonPath('tenant.phone', '+91 98765 43210')
        ->assertJsonPath('tenant.address', '12 Market Road')
        ->assertJsonStructure(['tenant' => ['id', 'name', 'email', 'logo_light_url', 'logo_dark_url']]);
});

test('the business profile can be updated', function () {
    $user = userWithTenant();

    $this->actingAs($user, 'sanctum')
        ->postJson('/api/tenant-profile', [
            'name' => 'Acme Jewellers',
            'email' => 'acme@example.com',
            'phone' => '+91 98765 43210',
            'address' => '12 Market Road',
        ])
        ->assertOk()
        ->assertJsonPath('tenant.name', 'Acme Jewellers');

    $tenant = $user->fresh()->tenant;

    expect($tenant->email)->toBe('acme@example.com');
    expect($tenant->slug)->toBe('acme-jewellers');
});

test('a logo can be uploaded from the app', function () {
    Storage::fake('public');
    $user = userWithTenant();

    $this->actingAs($user, 'sanctum')
        ->post('/api/tenant-profile', [
            'name' => 'Acme Jewellers',
            'email' => 'acme@example.com',
            'logo_light' => UploadedFile::fake()->image('logo.png', 200, 80),
        ])
        ->assertOk()
        ->assertJsonPath('tenant.logo_light_url', fn (?string $url) => $url !== null);

    $tenant = $user->fresh()->tenant;

    expect($tenant->logo_light_path)->not->toBeNull();
    Storage::disk('public')->assertExists($tenant->logo_light_path);
});

test('replacing a logo removes the previous file', function () {
    Storage::fake('public');
    $user = userWithTenant();

    $this->actingAs($user, 'sanctum')->post('/api/tenant-profile', [
        'name' => 'Acme Jewellers',
        'email' => 'acme@example.com',
        'logo_light' => UploadedFile::fake()->image('first.png', 200, 80),
    ]);

    $first = $user->fresh()->tenant->logo_light_path;

    $this->actingAs($user, 'sanctum')->post('/api/tenant-profile', [
        'name' => 'Acme Jewellers',
        'email' => 'acme@example.com',
        'logo_light' => UploadedFile::fake()->image('second.png', 200, 80),
    ]);

    $second = $user->fresh()->tenant->logo_light_path;

    expect($second)->not->toBe($first);
    Storage::disk('public')->assertMissing($first);
    Storage::disk('public')->assertExists($second);
});

test('a logo can be removed', function () {
    Storage::fake('public');
    $user = userWithTenant();

    $this->actingAs($user, 'sanctum')->post('/api/tenant-profile', [
        'name' => 'Acme Jewellers',
        'email' => 'acme@example.com',
        'logo_dark' => UploadedFile::fake()->image('logo.png', 200, 80),
    ]);

    $path = $user->fresh()->tenant->logo_dark_path;

    $this->actingAs($user, 'sanctum')
        ->post('/api/tenant-profile', [
            'name' => 'Acme Jewellers',
            'email' => 'acme@example.com',
            'remove_logo_dark' => true,
        ])
        ->assertOk()
        ->assertJsonPath('tenant.logo_dark_url', null);

    expect($user->fresh()->tenant->logo_dark_path)->toBeNull();
    Storage::disk('public')->assertMissing($path);
});

test('an oversized upload is rejected', function () {
    Storage::fake('public');
    $user = userWithTenant();

    $this->actingAs($user, 'sanctum')
        ->post('/api/tenant-profile', [
            'name' => 'Acme Jewellers',
            'email' => 'acme@example.com',
            'logo_light' => UploadedFile::fake()->image('huge.png')->size(3000),
        ], ['Accept' => 'application/json'])
        ->assertStatus(422)
        ->assertJsonValidationErrors('logo_light');
});

test('another business email cannot be taken', function () {
    $user = userWithTenant();
    $other = userWithTenant();

    $this->actingAs($user, 'sanctum')
        ->postJson('/api/tenant-profile', [
            'name' => 'Acme Jewellers',
            'email' => $other->tenant->email,
        ])
        ->assertStatus(422)
        ->assertJsonValidationErrors('email');
});

test('business profile endpoints require authentication', function () {
    $this->getJson('/api/tenant-profile')->assertUnauthorized();
    $this->postJson('/api/tenant-profile', [])->assertUnauthorized();
});
