<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { Building2, Plus, Trash2, Users } from 'lucide-vue-next';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';

interface Tenant {
    id: number;
    name: string;
    email: string;
    phone: string | null;
    status: 'active' | 'inactive' | 'suspended';
    users_count: number;
    customers_count: number;
    created_at: string;
}

interface PaginatedTenants {
    data: Tenant[];
    total: number;
    links: { url: string | null; label: string; active: boolean }[];
    last_page: number;
}

defineProps<{ tenants: PaginatedTenants }>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Tenants' },
];

const statusColor: Record<string, string> = {
    active: 'bg-green-50 text-green-700',
    inactive: 'bg-muted text-muted-foreground',
    suspended: 'bg-red-50 text-red-700',
};

function confirmDelete(tenant: Tenant) {
    if (confirm(`Delete tenant "${tenant.name}"? ALL their data will be permanently deleted.`)) {
        router.delete(`/admin/tenants/${tenant.id}`);
    }
}
</script>

<template>
    <Head title="Tenants — Admin" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-6 p-6">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-xl font-bold text-foreground">Tenants</h1>
                    <p class="text-sm text-muted-foreground">{{ tenants.total }} total tenants</p>
                </div>
                <Link href="/admin/tenants/create" class="flex items-center gap-2 rounded-xl bg-primary px-4 py-2.5 text-sm font-medium text-primary-foreground shadow-sm transition hover:bg-primary/90">
                    <Plus class="h-4 w-4" /> New Tenant
                </Link>
            </div>

            <div v-if="!tenants.data.length" class="rounded-2xl border border-dashed border-border p-16 text-center">
                <Building2 class="mx-auto mb-4 h-12 w-12 text-muted-foreground/30" />
                <h3 class="mb-1 font-semibold text-foreground">No tenants yet</h3>
                <p class="mb-6 text-sm text-muted-foreground">Create your first tenant to get started.</p>
                <Link href="/admin/tenants/create" class="rounded-xl bg-primary px-5 py-2.5 text-sm font-medium text-primary-foreground">
                    Create Tenant
                </Link>
            </div>

            <div v-else class="overflow-hidden rounded-2xl border border-border bg-card shadow-sm">
                <table class="w-full">
                    <thead>
                        <tr class="border-b border-border bg-muted/30">
                            <th class="px-5 py-3.5 text-left text-xs font-medium uppercase tracking-wide text-muted-foreground">Tenant</th>
                            <th class="px-5 py-3.5 text-left text-xs font-medium uppercase tracking-wide text-muted-foreground">Stats</th>
                            <th class="px-5 py-3.5 text-left text-xs font-medium uppercase tracking-wide text-muted-foreground">Status</th>
                            <th class="px-5 py-3.5"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-border">
                        <tr v-for="tenant in tenants.data" :key="tenant.id" class="transition hover:bg-muted/20">
                            <td class="px-5 py-4">
                                <div class="flex items-center gap-3">
                                    <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-primary/10 text-sm font-bold text-primary">
                                        {{ tenant.name.charAt(0) }}
                                    </div>
                                    <div>
                                        <p class="font-medium text-foreground">{{ tenant.name }}</p>
                                        <p class="text-xs text-muted-foreground">{{ tenant.email }}</p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-5 py-4">
                                <div class="flex gap-4 text-xs text-muted-foreground">
                                    <span class="flex items-center gap-1"><Users class="h-3.5 w-3.5" /> {{ tenant.users_count }}</span>
                                    <span>{{ tenant.customers_count }} customers</span>
                                </div>
                            </td>
                            <td class="px-5 py-4">
                                <span :class="['rounded-full px-2.5 py-0.5 text-xs font-medium capitalize', statusColor[tenant.status]]">
                                    {{ tenant.status }}
                                </span>
                            </td>
                            <td class="px-5 py-4">
                                <div class="flex items-center justify-end gap-2">
                                    <Link :href="`/admin/tenants/${tenant.id}/edit`" class="rounded-lg border border-border px-3 py-1.5 text-xs text-foreground transition hover:bg-muted">
                                        Edit
                                    </Link>
                                    <button class="rounded-lg border border-destructive/30 px-3 py-1.5 text-xs text-destructive transition hover:bg-destructive/10" @click="confirmDelete(tenant)">
                                        <Trash2 class="h-3.5 w-3.5" />
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div v-if="tenants.last_page > 1" class="flex items-center justify-center gap-1">
                <template v-for="link in tenants.links" :key="link.label">
                    <Link v-if="link.url" :href="link.url" :class="['rounded-lg px-3.5 py-2 text-sm transition', link.active ? 'bg-primary text-primary-foreground' : 'text-muted-foreground hover:bg-muted']" v-html="link.label" />
                    <span v-else class="rounded-lg px-3.5 py-2 text-sm text-muted-foreground opacity-40" v-html="link.label" />
                </template>
            </div>
        </div>
    </AppLayout>
</template>
