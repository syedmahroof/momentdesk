<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\CustomerDate;
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
        ];

        return Inertia::render('Dashboard', [
            'todayEvents' => $todayEvents,
            'upcomingEvents' => $upcomingEvents,
            'stats' => $stats,
            'eventsByType' => $this->eventsByType(),
        ]);
    }

    /**
     * Tracked customer dates grouped by event type.
     *
     * @return array<int, array{type: string, label: string, count: int}>
     */
    private function eventsByType(): array
    {
        $counts = CustomerDate::query()
            ->selectRaw('type, COUNT(*) as aggregate')
            ->groupBy('type')
            ->pluck('aggregate', 'type');

        $labels = [
            'birthday' => 'Birthdays',
            'wedding' => 'Anniversaries',
            'work' => 'Work milestones',
            'custom' => 'Custom events',
        ];

        return collect($labels)
            ->map(fn ($label, $type) => [
                'type' => $type,
                'label' => $label,
                'count' => (int) ($counts[$type] ?? 0),
            ])
            ->values()
            ->all();
    }
}
