<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\CustomerDate;
use App\Models\MessageLog;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(): Response
    {
        $todayEvents = CustomerDate::query()
            ->with('customer')
            ->today()
            ->get()
            ->map(fn ($date) => [
                'id' => $date->id,
                'customer_name' => $date->customer->name,
                'customer_id' => $date->customer_id,
                'display_title' => $date->display_title,
                'type' => $date->type,
                'years' => $date->years,
                'ordinal_years' => $date->ordinal_years,
                'whatsapp_number' => $date->customer->whatsapp_number ?? $date->customer->phone,
                'email' => $date->customer->email,
                'phone' => $date->customer->phone,
            ]);

        $upcomingEvents = CustomerDate::query()
            ->with('customer')
            ->upcoming(7)
            ->whereRaw("DATE_FORMAT(date, '%m-%d') != DATE_FORMAT(CURDATE(), '%m-%d')")
            ->orderByRaw("DATE_FORMAT(date, '%m-%d')")
            ->take(20)
            ->get()
            ->map(fn ($date) => [
                'id' => $date->id,
                'customer_name' => $date->customer->name,
                'customer_id' => $date->customer_id,
                'display_title' => $date->display_title,
                'type' => $date->type,
                'days_until' => $date->days_until,
                'years' => $date->years,
                'ordinal_years' => $date->ordinal_years,
            ]);

        $stats = [
            'total_customers' => Customer::query()->count(),
            'today_events' => $todayEvents->count(),
            'upcoming_events' => $upcomingEvents->count(),
            'sent_this_month' => MessageLog::query()
                ->where('status', 'sent')
                ->whereMonth('sent_at', now()->month)
                ->count(),
        ];

        return Inertia::render('Dashboard', [
            'todayEvents' => $todayEvents,
            'upcomingEvents' => $upcomingEvents,
            'stats' => $stats,
        ]);
    }
}
