<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\CustomerDate;
use App\Models\MessageLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CustomerController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $customers = Customer::query()
            ->with(['dates' => fn ($query) => $query->where('active', true)])
            ->withCount('dates')
            ->when($request->string('search')->toString(), function ($query, string $search) {
                $query->where(function ($query) use ($search) {
                    $query->where('name', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('whatsapp_number', 'like', "%{$search}%");
                });
            })
            ->latest()
            ->paginate(20)
            ->through(fn (Customer $customer) => $this->serializeCustomer($customer, withList: true));

        return response()->json($customers);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $this->validated($request);

        $customer = Customer::create(array_merge(
            collect($validated)->except('dates')->all(),
            ['created_by' => $request->user()->id],
        ));

        foreach ($validated['dates'] ?? [] as $dateData) {
            $customer->dates()->create($dateData);
        }

        return response()->json($this->serializeCustomer($customer->load('dates')), 201);
    }

    public function show(Customer $customer): JsonResponse
    {
        $customer->load([
            'dates' => fn ($query) => $query->orderBy('type'),
            'messageLogs' => fn ($query) => $query->with('template')->latest()->take(10),
        ]);

        return response()->json($this->serializeCustomer($customer, withDetail: true));
    }

    public function update(Request $request, Customer $customer): JsonResponse
    {
        $validated = $this->validated($request);

        $customer->update(collect($validated)->except('dates')->all());

        if ($request->has('dates')) {
            $customer->dates()->delete();
            foreach ($validated['dates'] ?? [] as $dateData) {
                $customer->dates()->create($dateData);
            }
        }

        return response()->json($this->serializeCustomer($customer->load('dates')));
    }

    public function destroy(Customer $customer): JsonResponse
    {
        $customer->delete();

        return response()->json(['message' => 'Customer deleted.']);
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:30'],
            'email' => ['nullable', 'email', 'max:255'],
            'whatsapp_number' => ['nullable', 'string', 'max:30'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'dates' => ['nullable', 'array'],
            'dates.*.type' => ['required', 'in:birthday,wedding,work,custom'],
            'dates.*.title' => ['nullable', 'string', 'max:255'],
            'dates.*.date' => ['required', 'date'],
            'dates.*.reminder_days_before' => ['nullable', 'integer', 'min:0', 'max:30'],
            'dates.*.active' => ['nullable', 'boolean'],
            'dates.*.auto_send' => ['nullable', 'boolean'],
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function serializeCustomer(Customer $customer, bool $withList = false, bool $withDetail = false): array
    {
        $data = [
            'id' => $customer->id,
            'name' => $customer->name,
            'phone' => $customer->phone,
            'email' => $customer->email,
            'whatsapp_number' => $customer->whatsapp_number,
            'notes' => $customer->notes,
            'created_at' => $customer->created_at?->toIso8601String(),
        ];

        if ($withList) {
            $data['dates_count'] = $customer->dates_count;
            $data['upcoming_event'] = $customer->dates
                ->map(fn (CustomerDate $date) => [
                    'display_title' => $date->display_title,
                    'days_until' => $date->days_until,
                    'type' => $date->type,
                ])
                ->sortBy('days_until')
                ->first();
        }

        if ($withDetail) {
            $data['dates'] = $customer->dates->map(fn (CustomerDate $date) => $this->serializeDate($date))->values();
            $data['message_logs'] = $customer->messageLogs->map(fn (MessageLog $log) => [
                'id' => $log->id,
                'channel' => $log->channel,
                'message' => $log->message,
                'status' => $log->status,
                'sent_at' => $log->sent_at?->diffForHumans(),
                'template' => $log->template ? ['id' => $log->template->id, 'name' => $log->template->name] : null,
            ])->values();
        }

        return $data;
    }

    /**
     * @return array<string, mixed>
     */
    private function serializeDate(CustomerDate $date): array
    {
        return [
            'id' => $date->id,
            'type' => $date->type,
            'title' => $date->title,
            'date' => $date->date->format('Y-m-d'),
            'reminder_days_before' => $date->reminder_days_before,
            'active' => $date->active,
            'auto_send' => $date->auto_send,
            'display_title' => $date->display_title,
            'ordinal_years' => $date->ordinal_years,
            'years' => $date->years,
            'days_until' => $date->days_until,
        ];
    }
}
