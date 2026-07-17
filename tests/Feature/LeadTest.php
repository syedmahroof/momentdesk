<?php

use App\Enums\LeadSource;
use App\Enums\LeadStatus;
use App\Models\Customer;
use App\Models\Lead;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Support\Facades\Route;

beforeEach(function (): void {
    $this->tenant = Tenant::factory()->create();
    $this->user = User::factory()->create([
        'tenant_id' => $this->tenant->id,
        'role' => 'admin',
    ]);
});

test('guests cannot view leads', function (): void {
    $this->get(route('leads.index'))->assertRedirect(route('login'));
});

test('authenticated users can view leads index', function (): void {
    Lead::factory()->for($this->tenant)->create(['name' => 'Alice Prospect']);

    $this->actingAs($this->user)
        ->get(route('leads.index'))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('Leads/Index')
            ->has('leads.data', 1)
            ->where('leads.data.0.name', 'Alice Prospect')
            ->has('statuses')
            ->has('sources')
        );
});

test('leads index only shows leads from the current tenant', function (): void {
    Lead::factory()->for($this->tenant)->create(['name' => 'Mine']);
    Lead::factory()->for(Tenant::factory()->create())->create(['name' => 'Theirs']);

    $this->actingAs($this->user)
        ->get(route('leads.index'))
        ->assertInertia(fn ($page) => $page
            ->has('leads.data', 1)
            ->where('leads.data.0.name', 'Mine')
        );
});

test('leads index can filter by status', function (): void {
    Lead::factory()->for($this->tenant)->create(['status' => LeadStatus::New]);
    Lead::factory()->for($this->tenant)->won()->create(['name' => 'Closed Deal']);

    $this->actingAs($this->user)
        ->get(route('leads.index', ['filter' => 'won']))
        ->assertInertia(fn ($page) => $page
            ->has('leads.data', 1)
            ->where('leads.data.0.name', 'Closed Deal')
            ->where('filters.filter', 'won')
        );
});

test('leads index can filter to follow-ups that are due', function (): void {
    Lead::factory()->for($this->tenant)->dueForFollowUp()->create(['name' => 'Chase Me']);
    Lead::factory()->for($this->tenant)->create([
        'name' => 'Later',
        'status' => LeadStatus::Contacted,
        'follow_up_at' => today()->addWeek(),
    ]);
    Lead::factory()->for($this->tenant)->create(['name' => 'No Date', 'follow_up_at' => null]);

    $this->actingAs($this->user)
        ->get(route('leads.index', ['filter' => 'due']))
        ->assertInertia(fn ($page) => $page
            ->has('leads.data', 1)
            ->where('leads.data.0.name', 'Chase Me')
            ->where('leads.data.0.follow_up_overdue', true)
            ->where('filters.filter', 'due')
        );
});

test('a due follow-up on a closed lead is not chased', function (): void {
    Lead::factory()->for($this->tenant)->won()->create(['follow_up_at' => today()->subDay()]);
    Lead::factory()->for($this->tenant)->lost()->create(['follow_up_at' => today()->subDay()]);

    $this->actingAs($this->user)
        ->get(route('leads.index', ['filter' => 'due']))
        ->assertInertia(fn ($page) => $page
            ->has('leads.data', 0)
            ->where('stats.due_follow_up', 0)
        );
});

test('an unknown index filter falls back to showing everything', function (): void {
    Lead::factory()->for($this->tenant)->count(2)->create();

    $this->actingAs($this->user)
        ->get(route('leads.index', ['filter' => 'bogus']))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->has('leads.data', 2)
            ->where('filters.filter', null)
        );
});

test('leads are managed from the index drawer, without dedicated create or edit pages', function (): void {
    expect(Route::has('leads.create'))->toBeFalse()
        ->and(Route::has('leads.edit'))->toBeFalse();
});

test('leads index reports pipeline stats', function (): void {
    Lead::factory()->for($this->tenant)->count(2)->create(['status' => LeadStatus::New, 'follow_up_at' => null]);
    Lead::factory()->for($this->tenant)->dueForFollowUp()->create();
    Lead::factory()->for($this->tenant)->converted()->create();

    $this->actingAs($this->user)
        ->get(route('leads.index'))
        ->assertInertia(fn ($page) => $page
            ->where('stats.open', 3)
            ->where('stats.due_follow_up', 1)
            ->where('stats.won', 1)
            ->where('stats.converted', 1)
        );
});

test('authenticated users can store a lead', function (): void {
    $this->actingAs($this->user)
        ->from(route('leads.index'))
        ->post(route('leads.store'), [
            'name' => 'Bob Prospect',
            'phone' => '+919876543210',
            'email' => 'bob@example.com',
            'source' => LeadSource::Referral->value,
            'follow_up_at' => today()->addDays(3)->toDateString(),
            'notes' => 'Asked about gold chains.',
        ])
        ->assertRedirect(route('leads.index'));

    $lead = Lead::firstWhere('name', 'Bob Prospect');

    expect($lead)->not->toBeNull()
        ->and($lead->tenant_id)->toBe($this->tenant->id)
        ->and($lead->created_by)->toBe($this->user->id)
        ->and($lead->source)->toBe(LeadSource::Referral)
        ->and($lead->status)->toBe(LeadStatus::New)
        ->and($lead->isConverted())->toBeFalse();
});

test('storing a lead requires a name and a valid source', function (): void {
    $this->actingAs($this->user)
        ->post(route('leads.store'), [
            'name' => '',
            'source' => 'carrier-pigeon',
        ])
        ->assertSessionHasErrors(['name', 'source']);

    expect(Lead::count())->toBe(0);
});

test('a new lead always starts at the first pipeline stage', function (): void {
    $this->actingAs($this->user)
        ->from(route('leads.index'))
        ->post(route('leads.store'), [
            'name' => 'Fresh Prospect',
            'source' => LeadSource::WalkIn->value,
            // Status is not part of the form, so a posted one must be ignored.
            'status' => LeadStatus::Won->value,
        ]);

    expect(Lead::firstWhere('name', 'Fresh Prospect')->status)->toBe(LeadStatus::New);
});

test('authenticated users can update a lead', function (): void {
    $lead = Lead::factory()->for($this->tenant)->create(['status' => LeadStatus::New]);

    // The drawer submits from a filtered index, which the redirect should preserve.
    $this->actingAs($this->user)
        ->from(route('leads.index', ['filter' => 'due']))
        ->put(route('leads.update', $lead), [
            'name' => 'Renamed Lead',
            'source' => LeadSource::Instagram->value,
            'follow_up_at' => today()->addDays(2)->toDateString(),
        ])
        ->assertRedirect(route('leads.index', ['filter' => 'due']));

    expect($lead->refresh())
        ->name->toBe('Renamed Lead')
        ->source->toBe(LeadSource::Instagram);

    expect($lead->follow_up_at->toDateString())->toBe(today()->addDays(2)->toDateString());
});

test('editing a lead cannot change its status', function (): void {
    $lead = Lead::factory()->for($this->tenant)->create(['status' => LeadStatus::New]);

    $this->actingAs($this->user)
        ->from(route('leads.index'))
        ->put(route('leads.update', $lead), [
            'name' => $lead->name,
            'source' => $lead->source->value,
            'status' => LeadStatus::Won->value,
        ]);

    expect($lead->refresh()->status)->toBe(LeadStatus::New);
});

test('a lead status is changed through its own endpoint', function (): void {
    $lead = Lead::factory()->for($this->tenant)->create([
        'name' => 'Dana Prospect',
        'status' => LeadStatus::New,
    ]);

    $this->actingAs($this->user)
        ->from(route('leads.index', ['filter' => 'due']))
        ->patch(route('leads.status', $lead), ['status' => LeadStatus::Qualified->value])
        ->assertRedirect(route('leads.index', ['filter' => 'due']))
        ->assertSessionHas('success', 'Dana Prospect moved to Qualified.');

    expect($lead->refresh()->status)->toBe(LeadStatus::Qualified);
});

test('a status change only accepts a real pipeline stage', function (): void {
    $lead = Lead::factory()->for($this->tenant)->create(['status' => LeadStatus::New]);

    $this->actingAs($this->user)
        ->from(route('leads.index'))
        ->patch(route('leads.status', $lead), ['status' => 'sold'])
        ->assertSessionHasErrors('status');

    expect($lead->refresh()->status)->toBe(LeadStatus::New);
});

test('changing a status leaves the rest of the lead untouched', function (): void {
    $lead = Lead::factory()->for($this->tenant)->create([
        'name' => 'Eve Prospect',
        'source' => LeadSource::Referral,
        'notes' => 'Wants a bangle.',
        'status' => LeadStatus::New,
    ]);

    $this->actingAs($this->user)
        ->from(route('leads.index'))
        ->patch(route('leads.status', $lead), ['status' => LeadStatus::Contacted->value]);

    expect($lead->refresh())
        ->name->toBe('Eve Prospect')
        ->source->toBe(LeadSource::Referral)
        ->notes->toBe('Wants a bangle.')
        ->status->toBe(LeadStatus::Contacted);
});

test('users cannot change the status of a lead from another tenant', function (): void {
    $lead = Lead::factory()->for(Tenant::factory()->create())->create(['status' => LeadStatus::New]);

    $this->actingAs($this->user)
        ->patch(route('leads.status', $lead), ['status' => LeadStatus::Won->value])
        ->assertNotFound();

    expect($lead->refresh()->status)->toBe(LeadStatus::New);
});

test('users cannot update a lead from another tenant', function (): void {
    $lead = Lead::factory()->for(Tenant::factory()->create())->create();

    $this->actingAs($this->user)
        ->put(route('leads.update', $lead), [
            'name' => 'Hijacked',
            'source' => LeadSource::Other->value,
            'status' => LeadStatus::New->value,
        ])
        ->assertNotFound();
});

test('authenticated users can delete a lead', function (): void {
    $lead = Lead::factory()->for($this->tenant)->create();

    $this->actingAs($this->user)
        ->from(route('leads.index'))
        ->delete(route('leads.destroy', $lead))
        ->assertRedirect(route('leads.index'));

    expect(Lead::count())->toBe(0);
});

test('converting a lead creates a linked customer and marks the lead won', function (): void {
    $lead = Lead::factory()->for($this->tenant)->create([
        'name' => 'Carol Prospect',
        'phone' => '+919876543210',
        'email' => 'carol@example.com',
        'whatsapp_number' => '+919876543211',
        'notes' => 'Wants a wedding set.',
        'status' => LeadStatus::Qualified,
    ]);

    $this->actingAs($this->user)
        ->post(route('leads.convert', $lead))
        ->assertRedirect();

    $customer = Customer::firstWhere('lead_id', $lead->id);

    expect($customer)->not->toBeNull()
        ->and($customer->name)->toBe('Carol Prospect')
        ->and($customer->tenant_id)->toBe($this->tenant->id)
        ->and($customer->phone)->toBe('+919876543210')
        ->and($customer->email)->toBe('carol@example.com')
        ->and($customer->whatsapp_number)->toBe('+919876543211')
        ->and($customer->notes)->toBe('Wants a wedding set.')
        ->and($customer->created_by)->toBe($this->user->id);

    expect($lead->refresh()->status)->toBe(LeadStatus::Won)
        ->and($lead->isConverted())->toBeTrue()
        ->and($lead->customer->id)->toBe($customer->id);
});

test('a lead cannot be converted twice', function (): void {
    $lead = Lead::factory()->for($this->tenant)->converted()->create();

    $this->actingAs($this->user)
        ->from(route('leads.index'))
        ->post(route('leads.convert', $lead))
        ->assertRedirect(route('leads.index'))
        ->assertSessionHas('error');

    expect(Customer::count())->toBe(0);
});

test('customers converted from leads are flagged in the customers list', function (): void {
    $lead = Lead::factory()->for($this->tenant)->create(['source' => LeadSource::Referral]);

    // Explicit timestamps keep the `latest()` ordering on the index deterministic.
    Customer::factory()->for($this->tenant)->create([
        'name' => 'From Lead',
        'lead_id' => $lead->id,
        'created_at' => now()->subHour(),
    ]);
    Customer::factory()->for($this->tenant)->create([
        'name' => 'Added Directly',
        'created_at' => now(),
    ]);

    $this->actingAs($this->user)
        ->get(route('customers.index'))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->has('customers.data', 2)
            ->where('customers.data.0.name', 'Added Directly')
            ->where('customers.data.0.from_lead', false)
            ->where('customers.data.1.name', 'From Lead')
            ->where('customers.data.1.from_lead', true)
            ->where('customers.data.1.lead_source_label', 'Referral')
        );
});
