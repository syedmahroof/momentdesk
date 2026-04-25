<?php

namespace App\Actions;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Support\Str;

class ProvisionTenantForUser
{
    public function __invoke(User $user): User
    {
        if ($user->tenant_id) {
            return $user;
        }

        $tenantName = $this->tenantNameFor($user);

        $tenant = Tenant::query()->firstOrCreate(
            ['email' => $user->email],
            [
                'name' => $tenantName,
                'slug' => $this->slugFor($tenantName),
                'status' => 'active',
            ],
        );

        $user->forceFill([
            'tenant_id' => $tenant->id,
            'role' => $user->isSuperAdmin() ? 'super_admin' : 'admin',
        ])->save();

        return $user->fresh();
    }

    private function tenantNameFor(User $user): string
    {
        $name = trim($user->name);

        if ($name === '') {
            return 'My Organization';
        }

        return "{$name}'s Organization";
    }

    private function slugFor(string $tenantName): string
    {
        $slug = Str::slug($tenantName);

        if ($slug === '') {
            $slug = 'organization';
        }

        return $slug.'-'.Str::lower(Str::random(6));
    }
}
