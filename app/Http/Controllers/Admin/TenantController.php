<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class TenantController extends Controller
{
    public function index(Request $request): Response
    {
        $filters = [
            'search' => $request->string('search')->trim()->value(),
            'status' => $request->string('status')->trim()->value(),
        ];

        $tenants = Tenant::query()
            ->withCount('users', 'customers')
            // Each row's users travel with the list so the detail drawer opens without a round trip.
            ->with(['users' => fn ($query) => $query->orderBy('name')->select('id', 'tenant_id', 'name', 'email', 'role')])
            ->when($filters['search'], fn ($query, string $search) => $query->where(
                fn ($query) => $query->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%")
            ))
            ->when($filters['status'], fn ($query, string $status) => $query->where('status', $status))
            ->latest()
            ->paginate(15)
            ->withQueryString()
            ->through(fn (Tenant $tenant): array => $this->toListItem($tenant));

        return Inertia::render('Admin/Tenants/Index', [
            'tenants' => $tenants,
            'filters' => $filters,
        ]);
    }

    /**
     * Map a tenant to the payload consumed by the admin tenants table and its drawer.
     *
     * @return array{id: int, name: string, email: string, phone: ?string, status: string, users_count: int, customers_count: int, created_at: string, users: list<array{id: int, name: string, email: string, role: string}>}
     */
    private function toListItem(Tenant $tenant): array
    {
        return [
            'id' => $tenant->id,
            'name' => $tenant->name,
            'email' => $tenant->email,
            'phone' => $tenant->phone,
            'status' => $tenant->status,
            'users_count' => $tenant->users_count,
            'customers_count' => $tenant->customers_count,
            'created_at' => $tenant->created_at->format('M j, Y'),
            'users' => $tenant->users
                ->map(fn (User $user): array => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => $user->role,
                ])
                ->all(),
        ];
    }

    public function update(Request $request, Tenant $tenant): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'unique:tenants,email,'.$tenant->id],
            'phone' => ['nullable', 'string', 'max:30'],
            'status' => ['required', 'in:active,inactive,suspended'],
        ]);

        $tenant->update($validated);

        // Back to the list the drawer was opened from, filters and page intact.
        return back()->with('success', 'Tenant updated.');
    }

    public function destroy(Tenant $tenant): RedirectResponse
    {
        $tenant->delete();

        return redirect()->route('admin.tenants.index')->with('success', 'Tenant deleted.');
    }
}
