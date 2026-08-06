<?php

use App\Models\Customer;
use App\Models\CustomerDate;
use App\Models\MessageLog;
use App\Models\Tenant;
use App\Models\User;

beforeEach(function (): void {
    $this->tenant = Tenant::factory()->create();
    $this->user = User::factory()->create([
        'tenant_id' => $this->tenant->id,
        'role' => 'admin',
    ]);

    $this->customer = Customer::factory()->create(['tenant_id' => $this->tenant->id]);
    $this->date = CustomerDate::factory()->create([
        'tenant_id' => $this->tenant->id,
        'customer_id' => $this->customer->id,
        'type' => 'birthday',
        'date' => now()->toDateString(),
    ]);
});

test('the send wish page renders without templates', function (): void {
    $this->actingAs($this->user)
        ->get(route('wishes.send', ['customer' => $this->customer->id, 'date' => $this->date->id]))
        ->assertSuccessful();
});

test('a wish can be sent with a free-text message', function (): void {
    $this->actingAs($this->user)
        ->post(route('wishes.store', ['customer' => $this->customer->id, 'date' => $this->date->id]), [
            'customer_id' => $this->customer->id,
            'customer_date_id' => $this->date->id,
            'channel' => 'email',
            'message' => 'Happy birthday!',
        ])
        ->assertSessionHasNoErrors();

    expect(MessageLog::query()->where('message', 'Happy birthday!')->exists())->toBeTrue();
});

test('wishes cannot be sent over the sms channel', function (): void {
    $this->actingAs($this->user)
        ->post(route('wishes.store', ['customer' => $this->customer->id, 'date' => $this->date->id]), [
            'customer_id' => $this->customer->id,
            'customer_date_id' => $this->date->id,
            'channel' => 'sms',
            'message' => 'Happy birthday!',
        ])
        ->assertSessionHasErrors('channel');
});

test('the templates routes no longer exist', function (): void {
    $this->actingAs($this->user)
        ->get('/templates')
        ->assertNotFound();
});
