<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Lead;
use App\Models\Tenant;
use App\Models\User;
use App\Scopes\TenantScope;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(): Response
    {
        $statusCounts = Tenant::query()
            ->selectRaw('status, COUNT(*) as aggregate')
            ->groupBy('status')
            ->pluck('aggregate', 'status');

        $stats = [
            'total_tenants' => (int) $statusCounts->sum(),
            'active_tenants' => (int) ($statusCounts['active'] ?? 0),
            'inactive_tenants' => (int) ($statusCounts['inactive'] ?? 0),
            'suspended_tenants' => (int) ($statusCounts['suspended'] ?? 0),
            'total_users' => User::query()->count(),
            'total_customers' => Customer::query()->withoutGlobalScope(TenantScope::class)->count(),
            'total_leads' => Lead::query()->withoutGlobalScope(TenantScope::class)->count(),
        ];

        $recentTenants = Tenant::query()
            ->withCount(['users', 'customers'])
            ->latest()
            ->take(5)
            ->get()
            ->map(fn (Tenant $tenant): array => [
                'id' => $tenant->id,
                'name' => $tenant->name,
                'email' => $tenant->email,
                'status' => $tenant->status,
                'users_count' => $tenant->users_count,
                'customers_count' => $tenant->customers_count,
                'created_at' => $tenant->created_at->diffForHumans(),
            ]);

        return Inertia::render('Admin/Dashboard', [
            'stats' => $stats,
            'recentTenants' => $recentTenants,
            'tenantSignups' => $this->tenantSignups(),
        ]);
    }

    /**
     * Tenant sign-up counts for the trailing 6 months, oldest first.
     *
     * @return array<int, array{label: string, count: int}>
     */
    private function tenantSignups(): array
    {
        $counts = Tenant::query()
            ->where('created_at', '>=', now()->subMonths(5)->startOfMonth())
            ->get(['created_at'])
            ->groupBy(fn (Tenant $tenant): string => $tenant->created_at->format('Y-m'))
            ->map->count();

        return collect(range(5, 0))
            ->map(fn (int $i): array => [
                'label' => now()->subMonths($i)->format('M'),
                'count' => (int) ($counts[now()->subMonths($i)->format('Y-m')] ?? 0),
            ])
            ->all();
    }
}
