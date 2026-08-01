<?php

namespace App\Http\Controllers;

use App\Http\Requests\WishRequest;
use App\Models\Customer;
use App\Models\CustomerDate;
use App\Services\WishService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class WishController extends Controller
{
    public function __construct(private readonly WishService $wishService) {}

    public function send(Customer $customer, CustomerDate $date): Response
    {
        return Inertia::render('Wishes/Send', [
            'customer' => $customer,
            'date' => array_merge($date->toArray(), [
                'display_title' => $date->display_title,
                'ordinal_years' => $date->ordinal_years,
                'years' => $date->years,
                'date' => $date->date->format('Y-m-d'),
            ]),
        ]);
    }

    public function store(WishRequest $request): RedirectResponse
    {
        $customer = Customer::findOrFail($request->customer_id);
        $date = CustomerDate::findOrFail($request->customer_date_id);

        if ($request->channel === 'whatsapp' && ! config('services.whatsapp.token')) {
            $link = $this->wishService->generateWhatsAppLink($customer, $request->message);
            $log = $this->wishService->send($customer, $date, 'whatsapp', $request->message);
            $log->update(['status' => 'sent', 'sent_at' => now()]);

            return back()->with([
                'success' => 'WhatsApp message ready.',
                'whatsapp_link' => $link,
            ]);
        }

        $this->wishService->send($customer, $date, $request->channel, $request->message);

        return back()->with('success', 'Message queued for delivery.');
    }
}
