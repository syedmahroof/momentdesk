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

        $sentThisMonth = MessageLog::query()
            ->where('status', 'sent')
            ->whereMonth('sent_at', now()->month)
            ->whereYear('sent_at', now()->year)
            ->count();

        $sentLastMonth = MessageLog::query()
            ->where('status', 'sent')
            ->whereMonth('sent_at', now()->subMonth()->month)
            ->whereYear('sent_at', now()->subMonth()->year)
            ->count();

        $stats = [
            'total_customers' => Customer::query()->count(),
            'today_events' => $todayEvents->count(),
            'upcoming_events' => $upcomingEvents->count(),
            'sent_this_month' => $sentThisMonth,
            'sent_trend' => $this->percentChange($sentLastMonth, $sentThisMonth),
        ];

        return Inertia::render('Dashboard', [
            'todayEvents' => $todayEvents,
            'upcomingEvents' => $upcomingEvents,
            'stats' => $stats,
            'monthlySends' => $this->monthlySends(),
            'eventsByType' => $this->eventsByType(),
        ]);
    }

    /**
     * Sent-message counts for the trailing 6 months, oldest first.
     *
     * @return array<int, array{label: string, count: int}>
     */
    private function monthlySends(): array
    {
        $counts = MessageLog::query()
            ->where('status', 'sent')
            ->whereNotNull('sent_at')
            ->where('sent_at', '>=', now()->subMonths(5)->startOfMonth())
            ->get(['sent_at'])
            ->groupBy(fn ($log) => $log->sent_at->format('Y-m'))
            ->map->count();

        return collect(range(5, 0))
            ->map(fn ($i) => [
                'label' => now()->subMonths($i)->format('M'),
                'count' => (int) ($counts[now()->subMonths($i)->format('Y-m')] ?? 0),
            ])
            ->all();
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

    private function percentChange(int $previous, int $current): ?int
    {
        if ($previous === 0) {
            return $current > 0 ? 100 : null;
        }

        return (int) round((($current - $previous) / $previous) * 100);
    }
}
