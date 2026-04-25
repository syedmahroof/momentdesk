<?php

use App\Models\FlyerTemplate;
use App\Models\Tenant;
use App\Models\User;

beforeEach(function (): void {
    $this->tenant = Tenant::factory()->create();
    $this->user = User::factory()->create([
        'tenant_id' => $this->tenant->id,
        'role' => 'admin',
    ]);
});

test('guests cannot view flyer templates', function (): void {
    $this->get(route('flyer-templates.index'))->assertRedirect(route('login'));
});

test('authenticated users can view flyer templates index', function (): void {
    $this->actingAs($this->user)
        ->get(route('flyer-templates.index'))
        ->assertSuccessful();
});

test('authenticated users can create a flyer template', function (): void {
    $elements = [
        [
            'id' => 'el-1',
            'type' => 'text',
            'key' => 'title',
            'label' => 'Title',
            'content' => null,
            'placeholder' => '{{title}}',
            'x' => 10,
            'y' => 20,
            'width' => 500,
            'height' => null,
            'font_size' => 32,
            'color' => '#111827',
            'alignment' => 'center',
            'font_weight' => 'bold',
        ],
    ];

    $this->actingAs($this->user)
        ->post(route('flyer-templates.store'), [
            'title' => 'Daily Gold Rate',
            'category' => 'daily_gold_rate',
            'paper_size' => 'a4',
            'canvas_width' => 1240,
            'canvas_height' => 1754,
            'background_type' => 'color',
            'background_color' => '#ffffff',
            'elements' => $elements,
            'is_active' => true,
        ])
        ->assertRedirect(route('flyer-templates.index'));

    expect(FlyerTemplate::query()->where('title', 'Daily Gold Rate')->exists())->toBeTrue();
});

test('authenticated users can update a flyer template', function (): void {
    $template = FlyerTemplate::factory()->for($this->tenant)->create([
        'title' => 'Original',
    ]);

    $elements = $template->elements;

    $this->actingAs($this->user)
        ->put(route('flyer-templates.update', $template), [
            'title' => 'Updated Title',
            'category' => $template->category,
            'paper_size' => $template->paper_size,
            'canvas_width' => $template->canvas_width,
            'canvas_height' => $template->canvas_height,
            'background_type' => $template->background_type,
            'background_color' => $template->background_color,
            'elements' => $elements,
            'is_active' => true,
        ])
        ->assertRedirect(route('flyer-templates.index'));

    expect($template->fresh()->title)->toBe('Updated Title');
});

test('authenticated users can delete a flyer template', function (): void {
    $template = FlyerTemplate::factory()->for($this->tenant)->create();

    $this->actingAs($this->user)
        ->delete(route('flyer-templates.destroy', $template))
        ->assertRedirect(route('flyer-templates.index'));

    expect(FlyerTemplate::query()->find($template->id))->toBeNull();
});
