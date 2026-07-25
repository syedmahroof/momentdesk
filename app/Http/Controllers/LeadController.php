<?php

namespace App\Http\Controllers;

use App\Enums\LeadSource;
use App\Enums\LeadStatus;
use App\Http\Requests\LeadRequest;
use App\Http\Requests\LeadStatusRequest;
use App\Models\Customer;
use App\Models\Lead;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class LeadController extends Controller
{
    /**
     * Index filter value for leads whose follow-up date has arrived.
     */
    private const DUE_FILTER = 'due';

    /**
     * `filter` is a single mutually-exclusive tab: `due` for leads whose
     * follow-up date has arrived, or any `LeadStatus` value.
     */
    public function index(Request $request): Response
    {
        $filter = $request->string('filter')->toString();

        $leads = Lead::query()
            ->with('customer:id,lead_id,name')
            ->when($filter === self::DUE_FILTER, fn ($query) => $query->dueForFollowUp())
            ->when(
                in_array($filter, LeadStatus::values(), true),
                fn ($query) => $query->where('status', $filter),
            )
            ->orderByRaw('follow_up_at is null')
            ->orderBy('follow_up_at')
            ->latest()
            ->paginate(20)
            ->withQueryString()
            ->through(fn (Lead $lead) => $this->toListItem($lead));

        $isKnownFilter = $filter === self::DUE_FILTER || in_array($filter, LeadStatus::values(), true);

        return Inertia::render('Leads/Index', [
            'leads' => $leads,
            'filters' => ['filter' => $isKnownFilter ? $filter : null],
            'statuses' => LeadStatus::options(),
            'sources' => LeadSource::options(),
            'stats' => [
                'open' => Lead::query()->open()->count(),
                'due_follow_up' => Lead::query()->dueForFollowUp()->count(),
                'won' => Lead::query()->where('status', LeadStatus::Won)->count(),
                'converted' => Lead::query()->whereNotNull('converted_at')->count(),
            ],
        ]);
    }

    public function store(LeadRequest $request): RedirectResponse
    {
        Lead::create(array_merge(
            $request->validated(),
            [
                'status' => LeadStatus::New,
                'created_by' => auth()->id(),
            ]
        ));

        return back()->with('success', 'Lead added successfully.');
    }

    public function update(LeadRequest $request, Lead $lead): RedirectResponse
    {
        $lead->update($request->validated());

        return back()->with('success', 'Lead updated.');
    }

    /**
     * Move a lead along the pipeline. Kept separate from `update()` so a status
     * change is always a deliberate action rather than a side effect of an edit.
     */
    public function updateStatus(LeadStatusRequest $request, Lead $lead): RedirectResponse
    {
        $status = LeadStatus::from($request->validated('status'));

        if ($lead->status === $status) {
            return back();
        }

        $lead->update(['status' => $status]);

        return back()->with('success', "{$lead->name} moved to {$status->label()}.");
    }

    public function destroy(Lead $lead): RedirectResponse
    {
        $lead->delete();

        return back()->with('success', 'Lead deleted.');
    }

    /**
     * Turn a lead into a customer, keeping the link so the customer is flagged
     * as lead-sourced in the customers list.
     */
    public function convert(Lead $lead): RedirectResponse
    {
        if ($lead->isConverted()) {
            return back()->with('error', 'This lead has already been converted.');
        }

        $customer = Customer::create([
            'tenant_id' => $lead->tenant_id,
            'lead_id' => $lead->id,
            'name' => $lead->name,
            'phone' => $lead->phone,
            'email' => $lead->email,
            'whatsapp_number' => $lead->whatsapp_number,
            'notes' => $lead->notes,
            'created_by' => auth()->id(),
        ]);

        $lead->update([
            'status' => LeadStatus::Won,
            'converted_at' => now(),
        ]);

        return redirect()->route('customers.edit', $customer)
            ->with('success', "{$lead->name} is now a customer. Add their important dates below.");
    }

    /**
     * @return array<string, mixed>
     */
    private function toListItem(Lead $lead): array
    {
        return [
            'id' => $lead->id,
            'name' => $lead->name,
            'phone' => $lead->phone,
            'email' => $lead->email,
            'whatsapp_number' => $lead->whatsapp_number,
            'source' => $lead->source->value,
            'source_label' => $lead->source->label(),
            'status' => $lead->status->value,
            'status_label' => $lead->status->label(),
            'status_badge_classes' => $lead->status->badgeClasses(),
            'follow_up_at' => $lead->follow_up_at?->format('Y-m-d'),
            'follow_up_overdue' => $lead->status->isOpen()
                && $lead->follow_up_at !== null
                && $lead->follow_up_at->isPast(),
            'notes' => $lead->notes,
            'is_converted' => $lead->isConverted(),
            'customer' => $lead->customer ? ['id' => $lead->customer->id, 'name' => $lead->customer->name] : null,
            'created_at' => $lead->created_at->diffForHumans(),
        ];
    }
}
