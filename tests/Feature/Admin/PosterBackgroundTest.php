<?php

use App\Models\Admin;
use App\Models\BackgroundCategory;
use App\Models\PosterBackground;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function (): void {
    Storage::fake('public');
    $this->admin = Admin::factory()->create();
    $this->category = BackgroundCategory::create(['name' => 'Bangle', 'slug' => 'bangle', 'order' => 1]);
});

test('guests cannot reach the library screen', function (): void {
    $this->get(route('admin.library'))->assertRedirect(route('admin.login'));
});

test('the library screen carries backgrounds and both kinds of category', function (): void {
    $this->actingAs($this->admin, 'admin')
        ->get(route('admin.library'))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('Admin/Library/Index')
            ->has('backgrounds')
            ->has('backgroundCategories')
            ->has('posterCategories')
        );
});

test('admins can upload background images into a category', function (): void {
    $this->actingAs($this->admin, 'admin')
        ->post(route('admin.poster-backgrounds.store'), [
            'background_category_id' => $this->category->id,
            'name' => 'Emerald Bangle',
            'images' => [UploadedFile::fake()->image('bangle.jpg', 1080, 1920)],
        ])
        ->assertSessionHasNoErrors();

    $background = PosterBackground::query()->firstOrFail();

    expect($background->name)->toBe('Emerald Bangle')
        ->and($background->background_category_id)->toBe($this->category->id)
        ->and($background->is_active)->toBeTrue();

    Storage::disk('public')->assertExists($background->path);
});

test('non-image uploads are rejected', function (): void {
    $this->actingAs($this->admin, 'admin')
        ->post(route('admin.poster-backgrounds.store'), [
            'background_category_id' => $this->category->id,
            'images' => [UploadedFile::fake()->create('notes.pdf', 100, 'application/pdf')],
        ])
        ->assertSessionHasErrors('images.0');

    expect(PosterBackground::query()->count())->toBe(0);
});

test('deleting a background removes its file', function (): void {
    $path = UploadedFile::fake()->image('ring.jpg')->store('poster-backgrounds', 'public');
    $background = PosterBackground::create([
        'background_category_id' => $this->category->id,
        'name' => 'Ring',
        'path' => $path,
    ]);

    $this->actingAs($this->admin, 'admin')
        ->delete(route('admin.poster-backgrounds.destroy', $background))
        ->assertSessionHasNoErrors();

    Storage::disk('public')->assertMissing($path);
    expect(PosterBackground::query()->count())->toBe(0);
});

test('deleting a category leaves its images uncategorized', function (): void {
    $background = PosterBackground::create([
        'background_category_id' => $this->category->id,
        'name' => 'Bangle art',
        'path' => 'poster-backgrounds/bangle.jpg',
    ]);

    $this->actingAs($this->admin, 'admin')
        ->delete(route('admin.background-categories.destroy', $this->category))
        ->assertSessionHasNoErrors();

    expect($background->fresh()->background_category_id)->toBeNull();
});

test('the admin poster editor has no background picker', function (): void {
    PosterBackground::create([
        'background_category_id' => $this->category->id,
        'name' => 'Visible',
        'path' => 'poster-backgrounds/visible.jpg',
        'is_active' => true,
    ]);

    $this->actingAs($this->admin, 'admin')
        ->get(route('admin.poster-templates.index'))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page->component('GoldPoster/Index')->missing('backgrounds'));
});

test('tenants only see active backgrounds in the poster editor', function (): void {
    $tenant = Tenant::factory()->create();
    $user = User::factory()->create(['tenant_id' => $tenant->id, 'role' => 'admin']);

    PosterBackground::create([
        'background_category_id' => $this->category->id,
        'name' => 'Visible',
        'path' => 'poster-backgrounds/visible.jpg',
        'is_active' => true,
    ]);
    PosterBackground::create([
        'background_category_id' => $this->category->id,
        'name' => 'Hidden',
        'path' => 'poster-backgrounds/hidden.jpg',
        'is_active' => false,
    ]);

    $this->actingAs($user)
        ->get(route('gold-poster.index'))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->has('backgrounds', 1)
            ->where('backgrounds.0.name', 'Visible')
            ->where('backgrounds.0.category', 'Bangle'));
});
