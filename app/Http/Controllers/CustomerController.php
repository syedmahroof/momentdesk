<?php

namespace App\Http\Controllers;

use App\Http\Requests\CustomerRequest;
use App\Models\Customer;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class CustomerController extends Controller
{
    public function index(): Response
    {
        $customers = Customer::query()
            ->with(['dates' => fn($q) => $q->where('active', true)])
            ->withCount('dates')
            ->latest()
            ->paginate(20)
            ->through(fn($c) => [
                'id' => $c->id,
                'name' => $c->name,
                'phone' => $c->phone,
                'email' => $c->email,
                'whatsapp_number' => $c->whatsapp_number,
                'dates_count' => $c->dates_count,
                'upcoming_event' => $c->dates
                    ->map(fn($d) => [
                        'display_title' => $d->display_title,
                        'days_until' => $d->days_until,
                        'type' => $d->type,
                    ])
                    ->sortBy('days_until')
                    ->first(),
                'created_at' => $c->created_at->diffForHumans(),
            ]);

        return Inertia::render('Customers/Index', [
            'customers' => $customers,
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Customers/Create');
    }

    public function store(CustomerRequest $request): RedirectResponse
    {
        $customer = Customer::create(array_merge(
            $request->safe()->except('dates'),
            ['created_by' => auth()->id()]
        ));

        foreach ($request->validated('dates', []) as $dateData) {
            $customer->dates()->create(array_merge($dateData, ['tenant_id' => $customer->tenant_id]));
        }

        return redirect()->route('customers.show', $customer)->with('success', 'Customer added successfully.');
    }

    public function show(Customer $customer): Response
    {
        $customer->load([
            'dates' => fn($q) => $q->orderBy('type'),
            'messageLogs' => fn($q) => $q->with('template')->latest()->take(10),
        ]);

        return Inertia::render('Customers/Show', [
            'customer' => array_merge($customer->toArray(), [
                'dates' => $customer->dates->map(fn($d) => array_merge($d->toArray(), [
                    'display_title' => $d->display_title,
                    'ordinal_years' => $d->ordinal_years,
                    'years' => $d->years,
                    'days_until' => $d->days_until,
                    'date' => $d->date->format('Y-m-d'),
                ])),
                'message_logs' => $customer->messageLogs->map(fn($log) => array_merge($log->toArray(), [
                    'sent_at' => $log->sent_at?->diffForHumans(),
                ])),
            ]),
        ]);
    }

    public function edit(Customer $customer): Response
    {
        $customer->load('dates');

        return Inertia::render('Customers/Edit', [
            'customer' => array_merge($customer->toArray(), [
                'dates' => $customer->dates->map(fn($d) => array_merge($d->toArray(), [
                    'date' => $d->date->format('Y-m-d'),
                ])),
            ]),
        ]);
    }

    public function update(CustomerRequest $request, Customer $customer): RedirectResponse
    {
        $customer->update($request->safe()->except('dates'));

        if ($request->has('dates')) {
            $customer->dates()->delete();
            foreach ($request->validated('dates', []) as $dateData) {
                $customer->dates()->create(array_merge($dateData, ['tenant_id' => $customer->tenant_id]));
            }
        }

        return redirect()->route('customers.show', $customer)->with('success', 'Customer updated.');
    }

    public function destroy(Customer $customer): RedirectResponse
    {
        $customer->delete();

        return redirect()->route('customers.index')->with('success', 'Customer deleted.');
    }
}
