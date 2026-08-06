<script setup lang="ts">
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { Building2, Mail, Phone, Search, Trash2, Users } from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';
import { Sheet, SheetContent, SheetDescription, SheetHeader, SheetTitle } from '@/components/ui/sheet';
import AdminLayout from '@/layouts/AdminLayout.vue';
import admin from '@/routes/admin';
import { type BreadcrumbItem } from '@/types';

interface TenantUser {
    id: number;
    name: string;
    email: string;
    role: string;
}

interface Tenant {
    id: number;
    name: string;
    email: string;
    phone: string | null;
    status: 'active' | 'inactive' | 'suspended';
    users_count: number;
    customers_count: number;
    created_at: string;
    users: TenantUser[];
}

interface PaginatedTenants {
    data: Tenant[];
    total: number;
    links: { url: string | null; label: string; active: boolean }[];
    last_page: number;
}

const props = defineProps<{
    tenants: PaginatedTenants;
    filters: { search: string | null; status: string | null };
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Admin', href: admin.dashboard().url },
    { title: 'Tenants' },
];

const search = ref(props.filters.search ?? '');
const status = ref(props.filters.status ?? '');

const statusColor: Record<Tenant['status'], string> = {
    active: 'bg-green-50 text-green-700 dark:bg-green-500/10 dark:text-green-400',
    inactive: 'bg-muted text-muted-foreground',
    suspended: 'bg-red-50 text-red-700 dark:bg-red-500/10 dark:text-red-400',
};

let searchTimeout: ReturnType<typeof setTimeout>;

watch([search, status], () => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(applyFilters, 300);
});

function applyFilters() {
    router.get(
        admin.tenants.index().url,
        { search: search.value || undefined, status: status.value || undefined },
        { preserveState: true, preserveScroll: true, replace: true },
    );
}

// ── Detail drawer ────────────────────────────────────────────────────
// Everything a tenant needs — details, users, deletion — lives here, so managing one
// never leaves the list or loses the current filters and page.
const openId = ref<number | null>(null);
const drawerOpen = computed({
    get: () => openId.value !== null,
    set: (value: boolean) => { if (!value) openId.value = null; },
});
const activeTenant = computed(() => props.tenants.data.find((t) => t.id === openId.value) ?? null);

const form = useForm({ name: '', email: '', phone: '', status: 'active' as Tenant['status'] });

function openTenant(tenant: Tenant) {
    openId.value = tenant.id;
    form.defaults({ name: tenant.name, email: tenant.email, phone: tenant.phone ?? '', status: tenant.status });
    form.reset();
    form.clearErrors();
}

function submit() {
    const tenant = activeTenant.value;
    if (!tenant) return;
    form.put(admin.tenants.update(tenant.id).url, {
        preserveScroll: true,
        onSuccess: () => { openId.value = null; },
    });
}

function confirmDelete(tenant: Tenant) {
    if (!confirm(`Delete tenant "${tenant.name}"? ALL their data will be permanently deleted.`)) return;
    router.delete(admin.tenants.destroy(tenant.id).url, {
        preserveScroll: true,
        onSuccess: () => { openId.value = null; },
    });
}

const fieldClass = 'w-full rounded-lg border border-border bg-background px-3 py-2.5 text-sm text-foreground focus:border-primary focus:outline-none';
</script>

<template>
    <Head title="Tenants — Admin" />

    <AdminLayout :breadcrumbs="breadcrumbs">
        <div class="mx-auto flex w-full max-w-7xl flex-col gap-6 p-4 sm:p-6 lg:p-8">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div>
                    <h1 class="text-xl font-bold text-foreground">Tenants</h1>
                    <p class="text-sm text-muted-foreground">{{ tenants.total }} total tenants</p>
                </div>
            </div>

            <!-- Filters -->
            <div class="flex flex-wrap items-center gap-3">
                <div class="relative min-w-0 flex-1 sm:max-w-xs">
                    <Search class="pointer-events-none absolute left-3 top-1/2 size-4 -translate-y-1/2 text-muted-foreground" />
                    <input
                        v-model="search"
                        type="search"
                        placeholder="Search name, email or phone"
                        class="w-full rounded-lg border border-border bg-background py-2.5 pl-9 pr-4 text-sm focus:border-primary focus:outline-none"
                    />
                </div>
                <select
                    v-model="status"
                    class="rounded-lg border border-border bg-background px-3 py-2.5 text-sm focus:border-primary focus:outline-none"
                >
                    <option value="">All statuses</option>
                    <option value="active">Active</option>
                    <option value="inactive">Inactive</option>
                    <option value="suspended">Suspended</option>
                </select>
            </div>

            <div v-if="!tenants.data.length" class="rounded-xl border border-dashed border-border p-16 text-center">
                <Building2 class="mx-auto mb-4 size-12 text-muted-foreground/30" />
                <h3 class="mb-1 font-semibold text-foreground">No tenants found</h3>
                <p class="mb-6 text-sm text-muted-foreground">Try a different search.</p>
            </div>

            <template v-else>
                <!-- Cards on small screens, where a five-column table can only scroll sideways -->
                <div class="grid gap-2.5 sm:hidden">
                    <button
                        v-for="tenant in tenants.data"
                        :key="tenant.id"
                        type="button"
                        class="flex items-center gap-3 rounded-xl border border-border bg-card p-3 text-left shadow-xs transition active:bg-muted/40"
                        @click="openTenant(tenant)"
                    >
                        <div class="flex size-10 shrink-0 items-center justify-center rounded-lg bg-primary/10 text-sm font-bold text-primary">
                            {{ tenant.name.charAt(0) }}
                        </div>
                        <div class="min-w-0 flex-1">
                            <p class="truncate font-medium text-foreground">{{ tenant.name }}</p>
                            <p class="truncate text-xs text-muted-foreground">{{ tenant.email }}</p>
                            <p class="mt-0.5 text-xs text-muted-foreground tabular-nums">{{ tenant.users_count }} users · {{ tenant.customers_count }} customers</p>
                        </div>
                        <span :class="['shrink-0 rounded-full px-2.5 py-0.5 text-xs font-medium capitalize', statusColor[tenant.status]]">
                            {{ tenant.status }}
                        </span>
                    </button>
                </div>

                <div class="hidden overflow-x-auto rounded-xl border border-border bg-card shadow-xs sm:block">
                    <table class="w-full min-w-[640px]">
                        <thead>
                            <tr class="border-b border-border bg-muted/30">
                                <th class="px-5 py-3.5 text-left text-xs font-medium uppercase tracking-wide text-muted-foreground">Tenant</th>
                                <th class="px-5 py-3.5 text-left text-xs font-medium uppercase tracking-wide text-muted-foreground">Usage</th>
                                <th class="px-5 py-3.5 text-left text-xs font-medium uppercase tracking-wide text-muted-foreground">Joined</th>
                                <th class="px-5 py-3.5 text-left text-xs font-medium uppercase tracking-wide text-muted-foreground">Status</th>
                                <th class="px-5 py-3.5"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-border">
                            <tr
                                v-for="tenant in tenants.data"
                                :key="tenant.id"
                                :class="['cursor-pointer transition hover:bg-muted/20', openId === tenant.id ? 'bg-primary/5' : '']"
                                @click="openTenant(tenant)"
                            >
                                <td class="px-5 py-4">
                                    <div class="flex items-center gap-3">
                                        <div class="flex size-9 shrink-0 items-center justify-center rounded-lg bg-primary/10 text-sm font-bold text-primary">
                                            {{ tenant.name.charAt(0) }}
                                        </div>
                                        <div class="min-w-0">
                                            <p class="truncate font-medium text-foreground">{{ tenant.name }}</p>
                                            <p class="truncate text-xs text-muted-foreground">{{ tenant.email }}</p>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-5 py-4">
                                    <div class="flex gap-4 text-xs text-muted-foreground">
                                        <span class="flex items-center gap-1 tabular-nums"><Users class="size-3.5" /> {{ tenant.users_count }}</span>
                                        <span class="tabular-nums">{{ tenant.customers_count }} customers</span>
                                    </div>
                                </td>
                                <td class="px-5 py-4 text-xs text-muted-foreground">{{ tenant.created_at }}</td>
                                <td class="px-5 py-4">
                                    <span :class="['rounded-full px-2.5 py-0.5 text-xs font-medium capitalize', statusColor[tenant.status]]">
                                        {{ tenant.status }}
                                    </span>
                                </td>
                                <td class="px-5 py-4 text-right">
                                    <span class="rounded-lg border border-border px-3 py-1.5 text-xs text-foreground">Manage</span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </template>

            <div v-if="tenants.last_page > 1" class="flex flex-wrap items-center justify-center gap-1">
                <template v-for="link in tenants.links" :key="link.label">
                    <Link
                        v-if="link.url"
                        :href="link.url"
                        preserve-scroll
                        :class="[
                            'rounded-lg px-3.5 py-2 text-sm transition',
                            link.active ? 'bg-primary text-primary-foreground' : 'text-muted-foreground hover:bg-muted',
                        ]"
                        v-html="link.label"
                    />
                    <span v-else class="rounded-lg px-3.5 py-2 text-sm text-muted-foreground opacity-40" v-html="link.label" />
                </template>
            </div>
        </div>

        <!-- Tenant drawer -->
        <Sheet v-model:open="drawerOpen">
            <SheetContent side="right" class="w-full overflow-y-auto sm:max-w-lg">
                <template v-if="activeTenant">
                    <SheetHeader>
                        <SheetTitle>{{ activeTenant.name }}</SheetTitle>
                        <SheetDescription>
                            {{ activeTenant.users_count }} users · {{ activeTenant.customers_count }} customers · joined {{ activeTenant.created_at }}
                        </SheetDescription>
                    </SheetHeader>

                    <form class="space-y-4 px-4" @submit.prevent="submit">
                        <div>
                            <label class="mb-1.5 block text-sm font-medium text-foreground">Organization name</label>
                            <input v-model="form.name" type="text" :class="fieldClass" />
                            <p v-if="form.errors.name" class="mt-1 text-xs text-destructive">{{ form.errors.name }}</p>
                        </div>
                        <div>
                            <label class="mb-1.5 block text-sm font-medium text-foreground">Email</label>
                            <input v-model="form.email" type="email" :class="fieldClass" />
                            <p v-if="form.errors.email" class="mt-1 text-xs text-destructive">{{ form.errors.email }}</p>
                        </div>
                        <div class="grid gap-4 sm:grid-cols-2">
                            <div>
                                <label class="mb-1.5 block text-sm font-medium text-foreground">Phone</label>
                                <input v-model="form.phone" type="tel" :class="fieldClass" />
                                <p v-if="form.errors.phone" class="mt-1 text-xs text-destructive">{{ form.errors.phone }}</p>
                            </div>
                            <div>
                                <label class="mb-1.5 block text-sm font-medium text-foreground">Status</label>
                                <select v-model="form.status" :class="fieldClass">
                                    <option value="active">Active</option>
                                    <option value="inactive">Inactive</option>
                                    <option value="suspended">Suspended</option>
                                </select>
                                <p v-if="form.errors.status" class="mt-1 text-xs text-destructive">{{ form.errors.status }}</p>
                            </div>
                        </div>
                        <button
                            type="submit"
                            :disabled="form.processing"
                            class="w-full rounded-lg bg-primary px-6 py-2.5 text-sm font-medium text-primary-foreground shadow-xs transition hover:bg-primary/90 disabled:opacity-70"
                        >
                            {{ form.processing ? 'Saving…' : 'Save changes' }}
                        </button>
                    </form>

                    <div class="px-4">
                        <h3 class="mb-2 text-xs font-semibold uppercase tracking-wide text-muted-foreground">Users</h3>
                        <p v-if="!activeTenant.users.length" class="rounded-lg border border-dashed border-border py-6 text-center text-sm text-muted-foreground">
                            This tenant has no users.
                        </p>
                        <ul v-else class="divide-y divide-border rounded-lg border border-border">
                            <li v-for="user in activeTenant.users" :key="user.id" class="flex items-center gap-3 px-3 py-2.5">
                                <div class="min-w-0 flex-1">
                                    <p class="truncate text-sm font-medium text-foreground">{{ user.name }}</p>
                                    <p class="truncate text-xs text-muted-foreground">{{ user.email }}</p>
                                </div>
                                <span class="shrink-0 rounded-full bg-muted px-2.5 py-0.5 text-xs font-medium capitalize text-muted-foreground">
                                    {{ user.role.replace('_', ' ') }}
                                </span>
                            </li>
                        </ul>
                    </div>

                    <div class="px-4 pb-6">
                        <h3 class="mb-2 text-xs font-semibold uppercase tracking-wide text-muted-foreground">Contact</h3>
                        <div class="space-y-1.5 text-sm text-muted-foreground">
                            <p class="flex items-center gap-2"><Mail class="size-3.5 shrink-0" /> <span class="truncate">{{ activeTenant.email }}</span></p>
                            <p class="flex items-center gap-2"><Phone class="size-3.5 shrink-0" /> {{ activeTenant.phone || '—' }}</p>
                        </div>

                        <button
                            type="button"
                            class="mt-5 flex w-full items-center justify-center gap-2 rounded-lg border border-destructive/30 px-4 py-2.5 text-sm font-medium text-destructive transition hover:bg-destructive/10"
                            @click="confirmDelete(activeTenant)"
                        >
                            <Trash2 class="size-4" /> Delete tenant
                        </button>
                        <p class="mt-2 text-center text-xs text-muted-foreground">
                            Removes its users, customers and message history permanently.
                        </p>
                    </div>
                </template>
            </SheetContent>
        </Sheet>
    </AdminLayout>
</template>
