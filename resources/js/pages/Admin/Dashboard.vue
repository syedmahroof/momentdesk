<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { ArrowRight, Building2, UserPlus, Users } from 'lucide-vue-next';
import { computed, type Component } from 'vue';
import AdminLayout from '@/layouts/AdminLayout.vue';
import { type BreadcrumbItem } from '@/types';
import admin from '@/routes/admin';

interface Stats {
    total_tenants: number;
    active_tenants: number;
    inactive_tenants: number;
    suspended_tenants: number;
    total_users: number;
    total_customers: number;
    total_leads: number;
}

interface RecentTenant {
    id: number;
    name: string;
    email: string;
    status: 'active' | 'inactive' | 'suspended';
    users_count: number;
    customers_count: number;
    created_at: string;
}

const props = defineProps<{
    stats: Stats;
    recentTenants: RecentTenant[];
    tenantSignups: { label: string; count: number }[];
}>();

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Admin', href: admin.dashboard().url }];

const statCards = computed<{ label: string; value: number; hint: string; icon: Component }[]>(() => [
    {
        label: 'Tenants',
        value: props.stats.total_tenants,
        hint: `${props.stats.active_tenants} active`,
        icon: Building2,
    },
    {
        label: 'Users',
        value: props.stats.total_users,
        hint: 'Across all tenants',
        icon: Users,
    },
    {
        label: 'Customers',
        value: props.stats.total_customers,
        hint: `${props.stats.total_leads} leads`,
        icon: UserPlus,
    },
]);

const maxSignups = computed(() => Math.max(1, ...props.tenantSignups.map((month) => month.count)));

const statusColor: Record<RecentTenant['status'], string> = {
    active: 'bg-green-50 text-green-700 dark:bg-green-500/10 dark:text-green-400',
    inactive: 'bg-muted text-muted-foreground',
    suspended: 'bg-red-50 text-red-700 dark:bg-red-500/10 dark:text-red-400',
};
</script>

<template>
    <Head title="Admin dashboard" />

    <AdminLayout :breadcrumbs="breadcrumbs">
        <div class="mx-auto flex w-full max-w-7xl flex-col gap-6 p-4 sm:p-6 lg:p-8">
            <div>
                <h1 class="text-xl font-bold text-foreground">Platform overview</h1>
                <p class="text-sm text-muted-foreground">Every tenant and user on MomentDesk.</p>
            </div>

            <!-- Stat cards -->
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                <div
                    v-for="card in statCards"
                    :key="card.label"
                    class="rounded-xl border border-border bg-card p-5 shadow-xs"
                >
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <p class="text-sm font-medium text-muted-foreground">{{ card.label }}</p>
                            <p class="mt-2 text-2xl font-bold tabular-nums text-foreground">{{ card.value }}</p>
                        </div>
                        <div class="flex size-9 shrink-0 items-center justify-center rounded-lg bg-primary/10 text-primary">
                            <component :is="card.icon" class="size-4.5" />
                        </div>
                    </div>
                    <p class="mt-3 text-xs text-muted-foreground">{{ card.hint }}</p>
                </div>
            </div>

            <div class="grid gap-6 lg:grid-cols-5">
                <!-- Sign-ups chart -->
                <div class="rounded-xl border border-border bg-card p-5 shadow-xs lg:col-span-2">
                    <h2 class="text-sm font-semibold text-foreground">New tenants</h2>
                    <p class="text-xs text-muted-foreground">Last 6 months</p>

                    <div class="mt-6 flex h-40 items-end gap-3">
                        <div
                            v-for="month in tenantSignups"
                            :key="month.label"
                            class="flex flex-1 flex-col items-center gap-2"
                        >
                            <span class="text-xs font-medium tabular-nums text-muted-foreground">{{ month.count }}</span>
                            <div
                                class="w-full rounded-t-md bg-primary/80 transition-all"
                                :style="{ height: `${Math.max(4, (month.count / maxSignups) * 100)}%` }"
                            />
                            <span class="text-xs text-muted-foreground">{{ month.label }}</span>
                        </div>
                    </div>

                    <div class="mt-6 grid grid-cols-3 gap-2 border-t border-border pt-4 text-center">
                        <div>
                            <p class="text-sm font-semibold tabular-nums text-foreground">{{ stats.active_tenants }}</p>
                            <p class="text-xs text-muted-foreground">Active</p>
                        </div>
                        <div>
                            <p class="text-sm font-semibold tabular-nums text-foreground">{{ stats.inactive_tenants }}</p>
                            <p class="text-xs text-muted-foreground">Inactive</p>
                        </div>
                        <div>
                            <p class="text-sm font-semibold tabular-nums text-foreground">{{ stats.suspended_tenants }}</p>
                            <p class="text-xs text-muted-foreground">Suspended</p>
                        </div>
                    </div>
                </div>

                <!-- Recent tenants -->
                <div class="rounded-xl border border-border bg-card shadow-xs lg:col-span-3">
                    <div class="flex items-center justify-between border-b border-border px-5 py-4">
                        <h2 class="text-sm font-semibold text-foreground">Newest tenants</h2>
                        <Link
                            :href="admin.tenants.index()"
                            class="flex items-center gap-1 text-xs font-medium text-primary hover:underline"
                        >
                            View all <ArrowRight class="size-3.5" />
                        </Link>
                    </div>

                    <div v-if="!recentTenants.length" class="px-5 py-12 text-center">
                        <Building2 class="mx-auto mb-3 size-10 text-muted-foreground/30" />
                        <p class="text-sm text-muted-foreground">No tenants yet.</p>
                    </div>

                    <ul v-else class="divide-y divide-border">
                        <li
                            v-for="tenant in recentTenants"
                            :key="tenant.id"
                            class="flex items-center gap-3 px-5 py-3.5"
                        >
                            <div class="flex size-9 shrink-0 items-center justify-center rounded-lg bg-primary/10 text-sm font-bold text-primary">
                                {{ tenant.name.charAt(0) }}
                            </div>
                            <div class="min-w-0 flex-1">
                                <p class="truncate text-sm font-medium text-foreground">{{ tenant.name }}</p>
                                <p class="truncate text-xs text-muted-foreground">
                                    {{ tenant.users_count }} users · {{ tenant.customers_count }} customers · {{ tenant.created_at }}
                                </p>
                            </div>
                            <span
                                :class="['shrink-0 rounded-full px-2.5 py-0.5 text-xs font-medium capitalize', statusColor[tenant.status]]"
                            >
                                {{ tenant.status }}
                            </span>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
