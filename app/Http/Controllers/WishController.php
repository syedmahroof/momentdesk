<?php

namespace App\Http\Controllers;

use App\Http\Requests\WishRequest;
use App\Models\Customer;
use App\Models\CustomerDate;
use App\Models\Template;
use App\Services\WishService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class WishController extends Controller
{
    public function __construct(private readonly WishService $wishService) {}

    public function send(Customer $customer, CustomerDate $date): Response
    {
        $templates = Template::query()
            ->where(fn ($q) => $q->where('type', $date->type)->orWhere('type', 'custom'))
            ->get();

        return Inertia::render('Wishes/Send', [
            'customer' => $customer,
            'date' => array_merge($date->toArray(), [
                'display_title' => $date->display_title,
                'ordinal_years' => $date->ordinal_years,
                'years' => $date->years,
                'date' => $date->date->format('Y-m-d'),
            ]),
            'templates' => $templates,
        ]);
    }

    public function store(WishRequest $request): RedirectResponse
    {
        $customer = Customer::findOrFail($request->customer_id);
        $date = CustomerDate::findOrFail($request->customer_date_id);
        $template = $request->template_id ? Template::find($request->template_id) : null;

        if ($request->channel === 'whatsapp' && ! config('services.whatsapp.token')) {
            $link = $this->wishService->generateWhatsAppLink($customer, $request->message);
            $log = $this->wishService->send($customer, $date, 'whatsapp', $request->message, $template);
            $log->update(['status' => 'sent', 'sent_at' => now()]);

            return back()->with([
                'success' => 'WhatsApp message ready.',
                'whatsapp_link' => $link,
            ]);
        }

        $this->wishService->send($customer, $date, $request->channel, $request->message, $template);

        return back()->with('success', 'Message queued for delivery.');
    }

    public function bulkSendToday(): RedirectResponse
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

        return back()->with('success', "Queued {$count} wishes for today's events.");
    }
}
