<?php

use App\Models\GoldRate;
use App\Models\PosterBackground;
use App\Models\User;

function saveRate($test, $user, string $date, int $p1, int $p8 = 0, int $p18 = 0)
{
    return $test->actingAs($user)->postJson(route('gold-rates.store'), [
        'date' => $date,
        'price_22k_1g' => $p1,
        'price_22k_8g' => $p8 ?: $p1 * 8,
        'price_18k_1g' => $p18 ?: (int) ($p1 * 0.8),
    ]);
}

test('the first saved rate has no previous to compare', function () {
    $user = User::factory()->create();
    saveRate($this, $user, '2026-04-01', 13000)
        ->assertOk()
        ->assertJsonPath('trend.has_previous', false)
        ->assertJsonPath('trend.direction', 'same');
});

test('an increase in the 1g 22K price is detected', function () {
    $user = User::factory()->create();
    saveRate($this, $user, '2026-04-01', 13000);
    saveRate($this, $user, '2026-04-02', 13140)
        ->assertJsonPath('trend.direction', 'up')
        ->assertJsonPath('trend.diff', 140);
});

test('a decrease in the 1g 22K price is detected', function () {
    $user = User::factory()->create();
    saveRate($this, $user, '2026-04-01', 13000);
    saveRate($this, $user, '2026-04-02', 12870)
        ->assertJsonPath('trend.direction', 'down')
        ->assertJsonPath('trend.diff', 130);
});

test('only the 1g 22K price drives the trend', function () {
    $user = User::factory()->create();
    saveRate($this, $user, '2026-04-01', 13000, 104000, 10400);
    // 1g22k unchanged, other prices changed → no change
    saveRate($this, $user, '2026-04-02', 13000, 105000, 10800)
        ->assertJsonPath('trend.direction', 'same');
});

test('saving the same date updates instead of duplicating', function () {
    $user = User::factory()->create();
    saveRate($this, $user, '2026-04-01', 13000);
    saveRate($this, $user, '2026-04-01', 13500);
    expect(GoldRate::query()->where('rate_date', '2026-04-01')->count())->toBe(1);
    expect(GoldRate::query()->where('rate_date', '2026-04-01')->first()->price_22k_1g)->toBe(13500);
});

test('latest returns the most recent rate for the tenant only', function () {
    $owner = User::factory()->create();
    saveRate($this, $owner, '2026-04-01', 13000);
    saveRate($this, $owner, '2026-04-03', 13200);

    $this->actingAs($owner)->getJson(route('gold-rates.latest'))
        ->assertOk()
        ->assertJsonPath('rate.price_22k_1g', 13200);

    $stranger = User::factory()->create();
    $this->actingAs($stranger)->getJson(route('gold-rates.latest'))
        ->assertOk()
        ->assertJsonPath('rate', null);
});

test('the rate history page lists saved rates', function () {
    $user = User::factory()->create();
    saveRate($this, $user, '2026-04-01', 13000);
    saveRate($this, $user, '2026-04-02', 13140);

    $this->actingAs($user)
        ->get(route('gold-rates.history'))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('GoldPoster/RateHistory')
            ->has('rates', 2)
            ->where('rates.1.price_22k_1g', 13140));
});

test('guests cannot view the rate history page', function () {
    $this->get(route('gold-rates.history'))->assertRedirect(route('login'));
});

test('the update screen offers the background library', function () {
    $user = User::factory()->create();

    PosterBackground::create([
        'name' => 'Bangle art',
        'path' => 'poster-backgrounds/bangle.jpg',
        'is_active' => true,
    ]);

    $this->actingAs($user)
        ->get(route('gold-poster.update'))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('GoldPoster/Update')
            ->has('backgrounds', 1)
            ->where('backgrounds.0.name', 'Bangle art'));
});
