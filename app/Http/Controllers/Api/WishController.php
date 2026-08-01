<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\CustomerDate;
use App\Models\Template;
use App\Services\WishService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WishController extends Controller
{
    public function __construct(private readonly WishService $wishService) {}

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'customer_id' => ['required', 'exists:customers,id'],
            'customer_date_id' => ['required', 'exists:customer_dates,id'],
            'channel' => ['required', 'in:whatsapp,email,sms'],
            'template_id' => ['nullable', 'exists:templates,id'],
            'message' => ['required', 'string', 'max:5000'],
        ]);

        $customer = Customer::findOrFail($validated['customer_id']);
        $date = CustomerDate::findOrFail($validated['customer_date_id']);
        $template = ! empty($validated['template_id']) ? Template::find($validated['template_id']) : null;

        if ($validated['channel'] === 'whatsapp' && ! config('services.whatsapp.token')) {
            $link = $this->wishService->generateWhatsAppLink($customer, $validated['message']);
            $log = $this->wishService->send($customer, $date, 'whatsapp', $validated['message'], $template);
            $log->update(['status' => 'sent', 'sent_at' => now()]);

            return response()->json([
                'message' => 'WhatsApp message ready.',
                'whatsapp_link' => $link,
            ]);
        }

        $this->wishService->send($customer, $date, $validated['channel'], $validated['message'], $template);

        return response()->json(['message' => 'Message queued for delivery.']);
    }

    public function bulkSendToday(): JsonResponse
    {
        $dates = CustomerDate::query()->with('customer')->today()->get();

        $count = 0;
        foreach ($dates as $date) {
            $customer = $date->customer;
            $template = Template::query()
                ->where('tenant_id', $customer->tenant_id)
                ->where('type', $date->type)
                ->where('is_default', true)
                ->first();

            if ($template) {
                $message = $template->renderFor($customer, $date);
                $this->wishService->send($customer, $date, $template->channel, $message, $template);
                $count++;
            }
        }

        return response()->json(['count' => $count]);
    }
}
