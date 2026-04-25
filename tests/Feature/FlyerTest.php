<?php

use App\Models\Flyer;
use App\Models\FlyerTemplate;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Http\UploadedFile;

beforeEach(function (): void {
    $this->tenant = Tenant::factory()->create();
    $this->user = User::factory()->create([
        'tenant_id' => $this->tenant->id,
        'role' => 'admin',
    ]);
});

test('guests cannot view flyers', function (): void {
    $this->get(route('flyers.index'))->assertRedirect(route('login'));
});

test('authenticated users can view flyers index', function (): void {
    $this->actingAs($this->user)
        ->get(route('flyers.index'))
        ->assertSuccessful();
});

test('authenticated users can view preview print page', function (): void {
    $this->actingAs($this->user)
        ->get(route('flyers.preview-print'))
        ->assertSuccessful();
});

test('authenticated users can store a flyer', function (): void {
    $template = FlyerTemplate::factory()->for($this->tenant)->create();

    $this->actingAs($this->user)
        ->post(route('flyers.store'), [
            'title' => 'Promo April',
            'flyer_template_id' => $template->id,
            'paper_size' => 'a4',
            'canvas_width' => $template->canvas_width,
            'canvas_height' => $template->canvas_height,
            'field_values' => [
                'title' => 'Hello',
                'date' => '2026-04-10',
                'price' => '$100',
                'message' => 'Test',
                'product_name' => 'Widget',
            ],
            'element_overrides' => [],
        ])
        ->assertRedirect();

    expect(Flyer::query()->where('title', 'Promo April')->exists())->toBeTrue();
});

test('authenticated users can store a flyer with a custom background image', function (): void {
    $template = FlyerTemplate::factory()->for($this->tenant)->create();

    $this->actingAs($this->user)
        ->post(route('flyers.store'), [
            'title' => 'Flyer With BG',
            'flyer_template_id' => $template->id,
            'paper_size' => 'a4',
            'canvas_width' => $template->canvas_width,
            'canvas_height' => $template->canvas_height,
            'field_values' => [
                'title' => 'Hello',
                'date' => '2026-04-10',
                'price' => '$100',
                'message' => 'Test',
                'product_name' => 'Widget',
            ],
            'element_overrides' => [],
            'background_image' => UploadedFile::fake()->image('bg.jpg', 120, 80),
        ])
        ->assertRedirect();

    $flyer = Flyer::query()->where('title', 'Flyer With BG')->first();

    expect($flyer)->not->toBeNull();
    expect($flyer->asset_paths)->toHaveKey('background');
    expect($flyer->template_snapshot['background_type'])->toBe('image');
    expect($flyer->template_snapshot['background_image_url'])->not->toBeEmpty();
});

test('authenticated users can view a saved flyer', function (): void {
    $template = FlyerTemplate::factory()->for($this->tenant)->create();

    $flyer = Flyer::factory()
        ->for($this->tenant)
        ->for($template)
        ->create();

    $this->actingAs($this->user)
        ->get(route('flyers.show', $flyer))
        ->assertSuccessful();
});
