<?php

use App\Models\PosterTemplate;
use App\Models\User;

test('a poster template can be saved, reloaded, renamed and deleted', function () {
    $user = User::factory()->create();
    $doc = [
        'version' => 1,
        'canvas' => ['w' => 1080, 'h' => 1920],
        'layers' => [['type' => 'text', 'text' => 'Hello']],
        'fields' => ['date' => '02 Apr 2026'],
    ];

    $created = $this->actingAs($user)
        ->postJson(route('poster-templates.store'), ['name' => 'My Poster', 'document' => $doc])
        ->assertCreated()
        ->assertJsonPath('name', 'My Poster');

    $id = $created->json('id');

    $this->actingAs($user)
        ->getJson(route('poster-templates.show', $id))
        ->assertOk()
        ->assertJsonPath('document.fields.date', '02 Apr 2026');

    $this->actingAs($user)
        ->putJson(route('poster-templates.update', $id), ['name' => 'Renamed', 'document' => $doc])
        ->assertOk()
        ->assertJsonPath('name', 'Renamed');

    $this->actingAs($user)
        ->deleteJson(route('poster-templates.destroy', $id))
        ->assertOk();

    expect(PosterTemplate::query()->withoutGlobalScopes()->find($id))->toBeNull();
});

test('editor guides are saved with the document and returned unchanged', function () {
    $user = User::factory()->create();
    $doc = [
        'version' => 1,
        'canvas' => ['w' => 1080, 'h' => 1920],
        'layers' => [],
        'guides' => ['v' => [540, 120], 'h' => [960]],
    ];

    $id = $this->actingAs($user)
        ->postJson(route('poster-templates.store'), ['name' => 'Guided', 'document' => $doc])
        ->assertCreated()
        ->json('id');

    $this->actingAs($user)
        ->getJson(route('poster-templates.show', $id))
        ->assertOk()
        ->assertJsonPath('document.guides.v', [540, 120])
        ->assertJsonPath('document.guides.h', [960]);
});

test('text box sizing and alignment are saved with a layer', function () {
    $user = User::factory()->create();
    $layer = [
        'type' => 'text',
        'text' => 'Wishing you a wonderful day',
        'align' => 'center',
        'vAlign' => 'bottom',
        'boxW' => 960,
        'boxH' => 240,
    ];

    $id = $this->actingAs($user)
        ->postJson(route('poster-templates.store'), ['name' => 'Boxed', 'document' => ['layers' => [$layer]]])
        ->assertCreated()
        ->json('id');

    $this->actingAs($user)
        ->getJson(route('poster-templates.show', $id))
        ->assertOk()
        ->assertJsonPath('document.layers.0.boxW', 960)
        ->assertJsonPath('document.layers.0.boxH', 240)
        ->assertJsonPath('document.layers.0.vAlign', 'bottom')
        ->assertJsonPath('document.layers.0.align', 'center');
});

test('background zoom and position are saved with the document', function () {
    $user = User::factory()->create();
    $doc = [
        'layers' => [],
        'bg' => ['color' => '#0d3b34', 'src' => 'https://example.test/bangle.jpg', 'scale' => 1.65, 'offsetX' => -120, 'offsetY' => 240],
    ];

    $id = $this->actingAs($user)
        ->postJson(route('poster-templates.store'), ['name' => 'Framed', 'document' => $doc])
        ->assertCreated()
        ->json('id');

    $this->actingAs($user)
        ->getJson(route('poster-templates.show', $id))
        ->assertOk()
        ->assertJsonPath('document.bg.scale', 1.65)
        ->assertJsonPath('document.bg.offsetX', -120)
        ->assertJsonPath('document.bg.offsetY', 240);
});

test('poster templates are scoped to the tenant', function () {
    $owner = User::factory()->create();
    $id = $this->actingAs($owner)
        ->postJson(route('poster-templates.store'), ['name' => 'A', 'document' => ['x' => 1]])
        ->json('id');

    $stranger = User::factory()->create();
    $this->actingAs($stranger)
        ->getJson(route('poster-templates.show', $id))
        ->assertNotFound();
});

test('name and document are required to save', function () {
    $user = User::factory()->create();
    $this->actingAs($user)
        ->postJson(route('poster-templates.store'), ['name' => ''])
        ->assertUnprocessable();
});

test('a poster template stores its category and free-form type', function () {
    $user = User::factory()->create();
    $id = $this->actingAs($user)
        ->postJson(route('poster-templates.store'), [
            'name' => 'Wedding wish',
            'category' => 'poster',
            'type' => 'Wedding',
            'document' => ['layers' => []],
        ])
        ->assertCreated()
        ->assertJsonPath('category', 'poster')
        ->assertJsonPath('type', 'Wedding')
        ->json('id');

    $this->actingAs($user)
        ->getJson(route('poster-templates.show', $id))
        ->assertOk()
        ->assertJsonPath('type', 'Wedding');
});

test('category defaults to gold_price when omitted', function () {
    $user = User::factory()->create();
    $this->actingAs($user)
        ->postJson(route('poster-templates.store'), ['name' => 'Rate', 'document' => ['layers' => []]])
        ->assertCreated()
        ->assertJsonPath('category', 'gold_price');
});

test('tenants cannot overwrite or delete an admin default template', function () {
    $default = PosterTemplate::query()->create([
        'tenant_id' => null,
        'name' => 'Emerald Rate Card',
        'category' => 'gold_price',
        'document' => ['version' => 1, 'layers' => []],
    ]);

    $user = User::factory()->create();

    // Readable — every tenant gets the default designs…
    $this->actingAs($user)
        ->getJson(route('poster-templates.show', $default))
        ->assertOk()
        ->assertJsonPath('is_global', true);

    // …but never writable, so one tenant's edits can't reach another.
    $this->actingAs($user)
        ->putJson(route('poster-templates.update', $default), ['name' => 'Hijacked', 'document' => ['version' => 1, 'layers' => []]])
        ->assertNotFound();

    $this->actingAs($user)
        ->deleteJson(route('poster-templates.destroy', $default))
        ->assertNotFound();

    expect($default->fresh()->name)->toBe('Emerald Rate Card');
});

test('editing a default template saves a private copy for that tenant only', function () {
    $default = PosterTemplate::query()->create([
        'tenant_id' => null,
        'name' => 'Emerald Rate Card',
        'category' => 'gold_price',
        'document' => ['version' => 1, 'layers' => []],
    ]);

    $user = User::factory()->create();

    $copyId = $this->actingAs($user)
        ->postJson(route('poster-templates.store'), [
            'name' => 'My Emerald Card',
            'document' => ['version' => 1, 'layers' => [['type' => 'text', 'text' => 'Mine']]],
        ])
        ->assertCreated()
        ->json('id');

    expect(PosterTemplate::query()->withoutGlobalScopes()->find($copyId)->tenant_id)->toBe($user->tenant_id)
        ->and($default->fresh()->document['layers'])->toBe([]);

    $otherTenantUser = User::factory()->create();
    $this->actingAs($otherTenantUser)
        ->getJson(route('poster-templates.show', $copyId))
        ->assertNotFound();
});
