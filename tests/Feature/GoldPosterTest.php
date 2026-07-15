<?php

use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

test('guests cannot access the gold poster page', function () {
    $this->get(route('gold-poster.index'))->assertRedirect(route('login'));
});

test('an authenticated tenant user can view the gold poster page', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('gold-poster.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('GoldPoster/Index')
            ->has('tenant')
            ->has('placeholders')
            ->where('placeholders.0.key', 'date')
        );
});

test('the placeholder enum exposes keyed options', function () {
    $options = App\Enums\Placeholder::options();

    expect($options)->toContain(['key' => 'customer_name', 'label' => 'Customer name', 'category' => 'poster'])
        ->and(collect($options)->pluck('key'))->toContain('price_22k_1g', 'status', 'message');
});
