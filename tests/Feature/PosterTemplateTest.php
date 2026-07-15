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
