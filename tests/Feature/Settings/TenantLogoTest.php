<?php

use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

test('a tenant logo can be uploaded as an svg', function () {
    Storage::fake('public');
    $user = User::factory()->create();

    $svg = '<?xml version="1.0" encoding="UTF-8"?><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><rect width="24" height="24" fill="#000"/></svg>';
    $file = UploadedFile::fake()->createWithContent('logo.svg', $svg);

    $this->actingAs($user)
        ->patch(route('tenant-profile.update'), [
            'name' => 'Acme Jewellers',
            'email' => 'acme@example.com',
            'logo_light' => $file,
        ])
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('tenant-profile.edit'));

    $tenant = $user->fresh()->tenant;
    expect($tenant->logo_light_path)->not->toBeNull();
    Storage::disk('public')->assertExists($tenant->logo_light_path);
});

test('a tenant logo can be uploaded as a png', function () {
    Storage::fake('public');
    $user = User::factory()->create();

    $this->actingAs($user)
        ->patch(route('tenant-profile.update'), [
            'name' => 'Acme Jewellers',
            'email' => 'acme@example.com',
            'logo_dark' => UploadedFile::fake()->image('logo.png', 200, 80),
        ])
        ->assertSessionHasNoErrors();

    expect($user->fresh()->tenant->logo_dark_path)->not->toBeNull();
});
