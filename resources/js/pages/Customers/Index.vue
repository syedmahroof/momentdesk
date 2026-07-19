<script setup lang="ts">
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import axios from 'axios';
import {
    Award,
    Cake,
    Heart,
    ImagePlus,
    Loader2,
    Mail,
    MessageCircle,
    Pencil,
    Phone,
    Plus,
    Search,
    Send,
    Star,
    Trash2,
    UserRoundCheck,
    UsersRound,
    X,
} from 'lucide-vue-next';
import { computed, ref, type Component } from 'vue';
import {
    Sheet,
    SheetContent,
    SheetDescription,
    SheetHeader,
    SheetTitle,
} from '@/components/ui/sheet';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem, type Customer } from '@/types';

interface PaginatedCustomers {
    data: Customer[];
    current_page: number;
    last_page: number;
    total: number;
    per_page: number;
    links: { url: string | null; label: string; active: boolean }[];
}

const props = defineProps<{ customers: PaginatedCustomers }>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Customers' },
];

const search = ref('');

const filteredCustomers = computed(() => {
    const term = search.value.trim().toLowerCase();
    if (!term) {
        return props.customers.data;
    }
    return props.customers.data.filter((customer) =>
        [customer.name, customer.email, customer.phone, customer.whatsapp_number]
            .filter(Boolean)
            .some((value) => String(value).toLowerCase().includes(term)),
    );
});

const eventTypeIcon: Record<string, Component> = {
    birthday: Cake,
    wedding: Heart,
    work: Award,
    custom: Star,
};

const eventTypeIconColor: Record<string, string> = {
    birthday: 'text-rose-500',
    wedding: 'text-violet-500',
    work: 'text-amber-500',
    custom: 'text-sky-500',
};

function confirmDelete(customer: { id: number; name: string }) {
    if (confirm(`Delete ${customer.name}? This cannot be undone.`)) {
        router.delete(`/customers/${customer.id}`, {
            onSuccess: () => {
                formOpen.value = false;
                viewOpen.value = false;
            },
        });
    }
}

interface CustomerDate {
    id?: number;
    type: string;
    title: string;
    date: string;
    reminder_days_before: number;
    active: boolean;
    auto_send: boolean;
    display_title?: string;
    days_until?: number;
    ordinal_years?: string;
    years?: number;
}

interface CustomerDetails {
    id: number;
    name: string;
    phone: string | null;
    email: string | null;
    whatsapp_number: string | null;
    notes: string | null;
    created_at: string;
    dates: CustomerDate[];
    message_logs: { id: number; channel: string; status: string; message: string; sent_at: string | null }[];
}

// ── Form drawer (add / edit) ─────────────────────────────────────────
const formOpen = ref(false);
const formMode = ref<'add' | 'edit'>('add');
const editingId = ref<number | null>(null);
const loadingForm = ref(false);

const dateTypes = [
    { value: 'birthday', label: 'Birthday' },
    { value: 'wedding', label: 'Anniversary' },
    { value: 'work', label: 'Work milestone' },
    { value: 'custom', label: 'Custom event' },
];

const form = useForm({
    name: '',
    phone: '',
    email: '',
    whatsapp_number: '',
    notes: '',
    dates: [] as CustomerDate[],
});

function addDate() {
    form.dates.push({ type: 'birthday', title: '', date: '', reminder_days_before: 1, active: true, auto_send: false });
}

function removeDate(index: number) {
    form.dates.splice(index, 1);
}

function openAdd() {
    formMode.value = 'add';
    editingId.value = null;
    form.reset();
    form.clearErrors();
    formOpen.value = true;
}

async function openEdit(id: number) {
    formMode.value = 'edit';
    editingId.value = id;
    form.clearErrors();
    formOpen.value = true;
    loadingForm.value = true;
    try {
        const { data } = await axios.get<CustomerDetails>(`/customers/${id}/details`);
        form.defaults({
            name: data.name,
            phone: data.phone ?? '',
            email: data.email ?? '',
            whatsapp_number: data.whatsapp_number ?? '',
            notes: data.notes ?? '',
            dates: data.dates.map((d) => ({
                type: d.type,
                title: d.title ?? '',
                date: d.date,
                reminder_days_before: d.reminder_days_before ?? 1,
                active: d.active,
                auto_send: d.auto_send,
            })),
        });
        form.reset();
    } finally {
        loadingForm.value = false;
    }
}

function submit() {
    const options = {
        preserveScroll: true,
        onSuccess: () => {
            formOpen.value = false;
            form.reset();
        },
    };
    if (formMode.value === 'edit' && editingId.value) {
        form.put(`/customers/${editingId.value}`, options);
    } else {
        form.post('/customers', options);
    }
}

// ── View drawer ──────────────────────────────────────────────────────
const viewOpen = ref(false);
const viewLoading = ref(false);
const viewCustomer = ref<CustomerDetails | null>(null);

const statusColor: Record<string, string> = {
    sent: 'text-emerald-700 bg-emerald-50 ring-emerald-200 dark:bg-emerald-500/10 dark:text-emerald-300 dark:ring-emerald-500/20',
    failed: 'text-red-700 bg-red-50 ring-red-200 dark:bg-red-500/10 dark:text-red-300 dark:ring-red-500/20',
    pending: 'text-amber-700 bg-amber-50 ring-amber-200 dark:bg-amber-500/10 dark:text-amber-300 dark:ring-amber-500/20',
    delivered: 'text-blue-700 bg-blue-50 ring-blue-200 dark:bg-blue-500/10 dark:text-blue-300 dark:ring-blue-500/20',
};

async function openView(id: number) {
    viewOpen.value = true;
    viewLoading.value = true;
    viewCustomer.value = null;
    try {
        const { data } = await axios.get<CustomerDetails>(`/customers/${id}/details`);
        viewCustomer.value = data;
    } finally {
        viewLoading.value = false;
    }
}

function editFromView() {
    if (!viewCustomer.value) {
        return;
    }
    const id = viewCustomer.value.id;
    viewOpen.value = false;
    openEdit(id);
}

const inputClass =
    'w-full rounded-md border border-input bg-background px-3 py-2 text-sm text-foreground placeholder:text-muted-foreground transition focus:border-ring focus:outline-none focus:ring-2 focus:ring-ring/25';
</script>

<template>
    <Head title="Customers" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="mx-auto flex w-full max-w-7xl flex-col gap-6 p-4 sm:p-6 lg:p-8">
            <!-- Header -->
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h1 class="text-2xl font-semibold tracking-tight text-foreground">Customers</h1>
                    <p class="text-sm text-muted-foreground">
                        {{ customers.total }} total customer{{ customers.total === 1 ? '' : 's' }}
                    </p>
                </div>
                <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
                    <div class="relative">
                        <Search class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                        <input
                            v-model="search"
                            type="search"
                            placeholder="Search this page…"
                            class="h-10 w-full rounded-md border border-input bg-background pl-9 pr-3 text-sm text-foreground shadow-xs transition placeholder:text-muted-foreground focus:border-ring focus:outline-none focus:ring-2 focus:ring-ring/30 sm:w-64"
                        />
                    </div>
                    <button
                        type="button"
                        class="inline-flex shrink-0 items-center gap-2 rounded-md bg-primary px-4 py-2.5 text-sm font-medium text-primary-foreground shadow-xs transition hover:bg-primary/90 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 focus-visible:ring-offset-background"
                        @click="openAdd"
                    >
                        <Plus class="h-4 w-4" />
                        Add customer
                    </button>
                </div>
            </div>

            <!-- Mobile card list (hidden on md+) -->
            <div class="flex flex-col gap-3 md:hidden">
                <div
                    v-for="customer in filteredCustomers"
                    :key="customer.id"
                    class="cursor-pointer rounded-lg border border-border bg-card p-4 shadow-xs transition hover:border-primary/30"
                    @click="openView(customer.id)"
                >
                    <div class="mb-2 flex items-start justify-between gap-2">
                        <div class="flex min-w-0 items-center gap-3">
                            <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-primary/10 text-sm font-semibold text-primary">
                                {{ customer.name.charAt(0).toUpperCase() }}
                            </div>
                            <div class="min-w-0">
                                <div class="flex items-center gap-1.5">
                                    <span class="truncate font-medium text-foreground">{{ customer.name }}</span>
                                    <span
                                        v-if="customer.from_lead"
                                        class="inline-flex shrink-0 items-center gap-1 rounded-full bg-violet-500/10 px-2 py-0.5 text-[10px] font-medium text-violet-600 dark:text-violet-400"
                                    >
                                        <UserRoundCheck class="h-3 w-3" /> Lead
                                    </span>
                                </div>
                                <p class="text-xs text-muted-foreground">Added {{ customer.created_at }}</p>
                            </div>
                        </div>
                        <span class="inline-flex h-6 min-w-6 items-center justify-center rounded-full bg-muted px-2 text-xs font-medium tabular-nums text-foreground">
                            {{ customer.dates_count }}
                        </span>
                    </div>

                    <div class="mb-3 flex flex-col gap-1 text-xs text-muted-foreground">
                        <span v-if="customer.phone" class="flex items-center gap-1.5">
                            <Phone class="h-3.5 w-3.5 shrink-0" />{{ customer.phone }}
                        </span>
                        <span v-if="customer.email" class="flex items-center gap-1.5">
                            <Mail class="h-3.5 w-3.5 shrink-0" /><span class="truncate">{{ customer.email }}</span>
                        </span>
                        <span v-if="customer.whatsapp_number" class="flex items-center gap-1.5 text-emerald-600 dark:text-emerald-400">
                            <MessageCircle class="h-3.5 w-3.5 shrink-0" />{{ customer.whatsapp_number }}
                        </span>
                    </div>

                    <div class="flex items-center justify-between border-t border-border pt-3" @click.stop>
                        <div v-if="customer.upcoming_event" class="flex items-center gap-2">
                            <component :is="eventTypeIcon[customer.upcoming_event.type] ?? Star" :class="['h-4 w-4 shrink-0', eventTypeIconColor[customer.upcoming_event.type]]" />
                            <p class="text-xs text-foreground">{{ customer.upcoming_event.display_title }} · in {{ customer.upcoming_event.days_until }}d</p>
                        </div>
                        <span v-else class="text-xs text-muted-foreground opacity-60">No upcoming event</span>
                        <div class="flex items-center gap-1">
                            <button class="rounded-md px-2.5 py-1.5 text-xs font-medium text-primary transition hover:bg-primary/10" @click="openEdit(customer.id)">
                                Edit
                            </button>
                            <button
                                class="rounded-md p-1.5 text-muted-foreground transition hover:bg-destructive/10 hover:text-destructive"
                                aria-label="Delete customer"
                                @click="confirmDelete(customer)"
                            >
                                <Trash2 class="h-4 w-4" />
                            </button>
                        </div>
                    </div>
                </div>

                <div v-if="!filteredCustomers.length" class="rounded-lg border border-dashed border-border bg-card px-5 py-16 text-center">
                    <div class="mx-auto mb-3 flex h-12 w-12 items-center justify-center rounded-full bg-muted">
                        <UsersRound class="h-6 w-6 text-muted-foreground" />
                    </div>
                    <p class="text-sm font-medium text-foreground">
                        {{ search ? 'No matching customers' : 'No customers found' }}
                    </p>
                    <p class="mt-0.5 text-sm text-muted-foreground">
                        {{ search ? `Nothing matches "${search}" on this page.` : 'Add your first customer to start tracking their moments.' }}
                    </p>
                </div>
            </div>

            <!-- Desktop table (hidden on mobile) -->
            <div class="hidden overflow-hidden rounded-lg border border-border bg-card shadow-xs md:block">
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm">
                        <thead>
                            <tr class="border-b border-border bg-muted/40 text-xs uppercase tracking-wide text-muted-foreground">
                                <th class="px-5 py-3 font-medium">Customer</th>
                                <th class="px-5 py-3 font-medium">Contact</th>
                                <th class="px-5 py-3 font-medium">Upcoming event</th>
                                <th class="px-5 py-3 text-center font-medium">Events</th>
                                <th class="px-5 py-3 text-right font-medium">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-border">
                            <tr
                                v-for="customer in filteredCustomers"
                                :key="customer.id"
                                class="group cursor-pointer transition hover:bg-muted/40"
                                @click="openView(customer.id)"
                            >
                                <td class="px-5 py-3">
                                    <div class="flex items-center gap-3">
                                        <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-primary/10 text-sm font-semibold text-primary">
                                            {{ customer.name.charAt(0).toUpperCase() }}
                                        </div>
                                        <div class="min-w-0">
                                            <div class="flex items-center gap-1.5">
                                                <span class="truncate font-medium text-foreground transition group-hover:text-primary">
                                                    {{ customer.name }}
                                                </span>
                                                <span
                                                    v-if="customer.from_lead"
                                                    :title="`Converted from a ${customer.lead_source_label} lead`"
                                                    class="inline-flex shrink-0 items-center gap-1 rounded-full bg-violet-500/10 px-2 py-0.5 text-[10px] font-medium text-violet-600 dark:text-violet-400"
                                                >
                                                    <UserRoundCheck class="h-3 w-3" />
                                                    Lead
                                                </span>
                                            </div>
                                            <p class="truncate text-xs text-muted-foreground">Added {{ customer.created_at }}</p>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-5 py-3">
                                    <div class="flex flex-col gap-1 text-xs text-muted-foreground">
                                        <span v-if="customer.phone" class="flex items-center gap-1.5">
                                            <Phone class="h-3.5 w-3.5 shrink-0" />{{ customer.phone }}
                                        </span>
                                        <span v-if="customer.email" class="flex items-center gap-1.5">
                                            <Mail class="h-3.5 w-3.5 shrink-0" /><span class="truncate">{{ customer.email }}</span>
                                        </span>
                                        <span v-if="customer.whatsapp_number" class="flex items-center gap-1.5 text-emerald-600 dark:text-emerald-400">
                                            <MessageCircle class="h-3.5 w-3.5 shrink-0" />{{ customer.whatsapp_number }}
                                        </span>
                                        <span v-if="!customer.phone && !customer.email && !customer.whatsapp_number" class="italic opacity-60">
                                            No contact info
                                        </span>
                                    </div>
                                </td>
                                <td class="px-5 py-3">
                                    <div v-if="customer.upcoming_event" class="flex items-center gap-2">
                                        <component :is="eventTypeIcon[customer.upcoming_event.type] ?? Star" :class="['h-4 w-4 shrink-0', eventTypeIconColor[customer.upcoming_event.type]]" />
                                        <div class="min-w-0">
                                            <p class="truncate text-foreground">{{ customer.upcoming_event.display_title }}</p>
                                            <p class="text-xs text-muted-foreground">
                                                in {{ customer.upcoming_event.days_until }} day{{ customer.upcoming_event.days_until === 1 ? '' : 's' }}
                                            </p>
                                        </div>
                                    </div>
                                    <span v-else class="text-xs text-muted-foreground opacity-60">—</span>
                                </td>
                                <td class="px-5 py-3 text-center">
                                    <span class="inline-flex h-6 min-w-6 items-center justify-center rounded-full bg-muted px-2 text-xs font-medium tabular-nums text-foreground">
                                        {{ customer.dates_count }}
                                    </span>
                                </td>
                                <td class="px-5 py-3" @click.stop>
                                    <div class="flex items-center justify-end gap-1">
                                        <button
                                            class="rounded-md px-2.5 py-1.5 text-xs font-medium text-muted-foreground transition hover:bg-muted hover:text-foreground"
                                            @click="openView(customer.id)"
                                        >
                                            View
                                        </button>
                                        <button
                                            class="rounded-md px-2.5 py-1.5 text-xs font-medium text-primary transition hover:bg-primary/10"
                                            @click="openEdit(customer.id)"
                                        >
                                            Edit
                                        </button>
                                        <button
                                            class="rounded-md p-1.5 text-muted-foreground transition hover:bg-destructive/10 hover:text-destructive"
                                            aria-label="Delete customer"
                                            @click="confirmDelete(customer)"
                                        >
                                            <Trash2 class="h-4 w-4" />
                                        </button>
                                    </div>
                                </td>
                            </tr>

                            <tr v-if="!filteredCustomers.length">
                                <td colspan="5" class="px-5 py-16 text-center">
                                    <div class="mx-auto mb-3 flex h-12 w-12 items-center justify-center rounded-full bg-muted">
                                        <UsersRound class="h-6 w-6 text-muted-foreground" />
                                    </div>
                                    <p class="text-sm font-medium text-foreground">
                                        {{ search ? 'No matching customers' : 'No customers found' }}
                                    </p>
                                    <p class="mt-0.5 text-sm text-muted-foreground">
                                        {{ search ? `Nothing matches “${search}” on this page.` : 'Add your first customer to start tracking their moments.' }}
                                    </p>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Pagination -->
            <div v-if="customers.last_page > 1" class="flex items-center justify-center gap-1">
                <template v-for="link in customers.links" :key="link.label">
                    <Link
                        v-if="link.url"
                        :href="link.url"
                        :class="[
                            'inline-flex h-9 min-w-9 items-center justify-center rounded-md px-3 text-sm transition',
                            link.active ? 'bg-primary text-primary-foreground shadow-xs' : 'text-muted-foreground hover:bg-muted hover:text-foreground',
                        ]"
                        v-html="link.label"
                    />
                    <span v-else class="inline-flex h-9 min-w-9 items-center justify-center rounded-md px-3 text-sm text-muted-foreground opacity-40" v-html="link.label" />
                </template>
            </div>
        </div>

        <!-- ── View drawer ── -->
        <Sheet v-model:open="viewOpen">
            <SheetContent class="flex w-full flex-col gap-0 p-0 sm:max-w-lg">
                <SheetHeader class="border-b border-border px-6 py-4 text-left">
                    <SheetTitle>Customer details</SheetTitle>
                    <SheetDescription>View contact info, tracked dates and message history.</SheetDescription>
                </SheetHeader>

                <div v-if="viewLoading" class="flex flex-1 items-center justify-center py-20 text-muted-foreground">
                    <Loader2 class="h-6 w-6 animate-spin" />
                </div>

                <div v-else-if="viewCustomer" class="flex min-h-0 flex-1 flex-col">
                    <div class="flex-1 space-y-6 overflow-y-auto px-6 py-5">
                        <!-- Profile -->
                        <div class="flex items-start gap-3">
                            <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-full bg-primary/10 text-lg font-semibold text-primary">
                                {{ viewCustomer.name.charAt(0).toUpperCase() }}
                            </div>
                            <div class="min-w-0">
                                <h3 class="text-base font-semibold text-foreground">{{ viewCustomer.name }}</h3>
                                <p class="text-xs text-muted-foreground">Added {{ viewCustomer.created_at }}</p>
                            </div>
                        </div>

                        <!-- Contact -->
                        <div class="grid gap-2 text-sm">
                            <div v-if="viewCustomer.phone" class="flex items-center gap-2 text-muted-foreground">
                                <Phone class="h-4 w-4" />{{ viewCustomer.phone }}
                            </div>
                            <div v-if="viewCustomer.email" class="flex items-center gap-2 text-muted-foreground">
                                <Mail class="h-4 w-4" />{{ viewCustomer.email }}
                            </div>
                            <div v-if="viewCustomer.whatsapp_number" class="flex items-center gap-2 text-emerald-600 dark:text-emerald-400">
                                <MessageCircle class="h-4 w-4" />{{ viewCustomer.whatsapp_number }}
                            </div>
                        </div>

                        <p v-if="viewCustomer.notes" class="rounded-md bg-muted/50 px-3 py-2 text-sm text-muted-foreground">
                            {{ viewCustomer.notes }}
                        </p>

                        <!-- Dates -->
                        <div>
                            <h4 class="mb-2 text-xs font-semibold uppercase tracking-wide text-muted-foreground">Important dates</h4>
                            <div v-if="!viewCustomer.dates.length" class="rounded-md border border-dashed border-border px-4 py-5 text-center text-xs text-muted-foreground">
                                No dates tracked yet.
                            </div>
                            <ul v-else class="divide-y divide-border rounded-md border border-border">
                                <li v-for="date in viewCustomer.dates" :key="date.id" class="flex items-center justify-between gap-3 px-3 py-2.5">
                                    <div class="flex min-w-0 items-center gap-2.5">
                                        <component :is="eventTypeIcon[date.type] ?? Star" :class="['h-4 w-4 shrink-0', eventTypeIconColor[date.type]]" />
                                        <div class="min-w-0">
                                            <p class="truncate text-sm font-medium text-foreground">{{ date.display_title }}</p>
                                            <p class="text-xs text-muted-foreground">
                                                {{ date.date }}<span v-if="date.years && date.years > 0"> · {{ date.ordinal_years }}</span>
                                            </p>
                                        </div>
                                    </div>
                                    <Link
                                        :href="`/customers/${viewCustomer.id}/dates/${date.id}/send`"
                                        class="inline-flex shrink-0 items-center gap-1.5 rounded-md bg-primary px-2.5 py-1.5 text-xs font-medium text-primary-foreground transition hover:bg-primary/90"
                                    >
                                        <Send class="h-3.5 w-3.5" /> Send
                                    </Link>
                                </li>
                            </ul>
                        </div>

                        <!-- History -->
                        <div v-if="viewCustomer.message_logs.length">
                            <h4 class="mb-2 text-xs font-semibold uppercase tracking-wide text-muted-foreground">Message history</h4>
                            <ul class="divide-y divide-border rounded-md border border-border">
                                <li v-for="log in viewCustomer.message_logs" :key="log.id" class="flex items-start justify-between gap-3 px-3 py-2.5">
                                    <div class="min-w-0">
                                        <p class="line-clamp-2 text-sm text-foreground">{{ log.message }}</p>
                                        <p class="mt-0.5 text-xs uppercase text-muted-foreground">{{ log.channel }}<span v-if="log.sent_at"> · {{ log.sent_at }}</span></p>
                                    </div>
                                    <span :class="['shrink-0 rounded-full px-2 py-0.5 text-xs font-medium capitalize ring-1 ring-inset', statusColor[log.status]]">{{ log.status }}</span>
                                </li>
                            </ul>
                        </div>
                    </div>

                    <!-- Footer -->
                    <div class="flex items-center justify-between gap-3 border-t border-border px-6 py-4">
                        <button
                            class="inline-flex items-center gap-1.5 rounded-md border border-destructive/30 px-3 py-2 text-sm font-medium text-destructive transition hover:bg-destructive/10"
                            @click="confirmDelete(viewCustomer)"
                        >
                            <Trash2 class="h-4 w-4" /> Delete
                        </button>
                        <div class="flex items-center gap-2">
                            <Link
                                :href="`/customers/${viewCustomer.id}/poster`"
                                class="inline-flex items-center gap-1.5 rounded-md border border-border px-3 py-2 text-sm font-medium text-foreground transition hover:bg-muted"
                            >
                                <ImagePlus class="h-4 w-4" /> Poster
                            </Link>
                            <button
                                class="inline-flex items-center gap-1.5 rounded-md bg-primary px-4 py-2 text-sm font-medium text-primary-foreground shadow-xs transition hover:bg-primary/90"
                                @click="editFromView"
                            >
                                <Pencil class="h-4 w-4" /> Edit
                            </button>
                        </div>
                    </div>
                </div>
            </SheetContent>
        </Sheet>

        <!-- ── Add / edit drawer ── -->
        <Sheet v-model:open="formOpen">
            <SheetContent class="flex w-full flex-col gap-0 p-0 sm:max-w-xl">
                <SheetHeader class="border-b border-border px-6 py-4 text-left">
                    <SheetTitle>{{ formMode === 'edit' ? 'Edit customer' : 'Add customer' }}</SheetTitle>
                    <SheetDescription>
                        {{ formMode === 'edit' ? 'Update this customer and their tracked dates.' : 'Create a customer and optionally track their special dates.' }}
                    </SheetDescription>
                </SheetHeader>

                <div v-if="loadingForm" class="flex flex-1 items-center justify-center py-20 text-muted-foreground">
                    <Loader2 class="h-6 w-6 animate-spin" />
                </div>

                <form v-else class="flex min-h-0 flex-1 flex-col" @submit.prevent="submit">
                    <div class="flex-1 space-y-5 overflow-y-auto px-6 py-5">
                        <div class="space-y-3">
                            <div>
                                <label class="mb-1.5 block text-sm font-medium text-foreground">Full name *</label>
                                <input v-model="form.name" type="text" placeholder="e.g. Alice Johnson" :class="inputClass" />
                                <p v-if="form.errors.name" class="mt-1 text-xs text-destructive">{{ form.errors.name }}</p>
                            </div>
                            <div class="grid gap-3 sm:grid-cols-2">
                                <div>
                                    <label class="mb-1.5 block text-sm font-medium text-foreground">Phone</label>
                                    <input v-model="form.phone" type="tel" placeholder="+1 234 567 8900" :class="inputClass" />
                                </div>
                                <div>
                                    <label class="mb-1.5 block text-sm font-medium text-foreground">Email</label>
                                    <input v-model="form.email" type="email" placeholder="alice@example.com" :class="inputClass" />
                                </div>
                            </div>
                            <div>
                                <label class="mb-1.5 block text-sm font-medium text-foreground">WhatsApp number</label>
                                <input v-model="form.whatsapp_number" type="tel" placeholder="+1 234 567 8900" :class="inputClass" />
                            </div>
                            <div>
                                <label class="mb-1.5 block text-sm font-medium text-foreground">Notes</label>
                                <textarea v-model="form.notes" rows="2" placeholder="Any additional notes…" :class="inputClass" />
                            </div>
                        </div>

                        <div class="border-t border-border pt-4">
                            <div class="mb-3 flex items-center justify-between">
                                <h3 class="text-sm font-semibold text-foreground">Important dates</h3>
                                <button type="button" class="inline-flex items-center gap-1.5 rounded-md border border-border px-2.5 py-1.5 text-xs font-medium text-foreground transition hover:bg-muted" @click="addDate">
                                    <Plus class="h-3.5 w-3.5" /> Add date
                                </button>
                            </div>

                            <p v-if="!form.dates.length" class="rounded-md border border-dashed border-border px-4 py-5 text-center text-xs text-muted-foreground">
                                No dates yet. Add a birthday, anniversary, or custom event.
                            </p>

                            <div v-else class="space-y-3">
                                <div v-for="(date, index) in form.dates" :key="index" class="relative rounded-md border border-border p-3">
                                    <button type="button" class="absolute right-2 top-2 rounded-md p-1 text-muted-foreground transition hover:bg-destructive/10 hover:text-destructive" @click="removeDate(index)">
                                        <X class="h-3.5 w-3.5" />
                                    </button>
                                    <div class="grid gap-2.5 sm:grid-cols-2">
                                        <div>
                                            <label class="mb-1 block text-xs font-medium text-muted-foreground">Type</label>
                                            <select v-model="date.type" :class="inputClass">
                                                <option v-for="t in dateTypes" :key="t.value" :value="t.value">{{ t.label }}</option>
                                            </select>
                                        </div>
                                        <div>
                                            <label class="mb-1 block text-xs font-medium text-muted-foreground">Date</label>
                                            <input v-model="date.date" type="date" :class="inputClass" />
                                        </div>
                                    </div>
                                    <div v-if="date.type === 'custom'" class="mt-2.5">
                                        <label class="mb-1 block text-xs font-medium text-muted-foreground">Custom title</label>
                                        <input v-model="date.title" type="text" placeholder="e.g. Graduation" :class="inputClass" />
                                    </div>
                                    <div class="mt-2.5 flex items-center gap-4">
                                        <label class="flex cursor-pointer items-center gap-2 text-xs text-foreground">
                                            <input v-model="date.active" type="checkbox" class="h-4 w-4 rounded border-border accent-primary" /> Active
                                        </label>
                                        <label class="flex cursor-pointer items-center gap-2 text-xs text-foreground">
                                            <input v-model="date.auto_send" type="checkbox" class="h-4 w-4 rounded border-border accent-primary" /> Auto send
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="flex items-center justify-end gap-3 border-t border-border px-6 py-4">
                        <button type="button" class="rounded-md border border-border px-4 py-2 text-sm font-medium text-foreground transition hover:bg-muted" @click="formOpen = false">
                            Cancel
                        </button>
                        <button type="submit" :disabled="form.processing" class="inline-flex items-center gap-2 rounded-md bg-primary px-5 py-2 text-sm font-medium text-primary-foreground shadow-xs transition hover:bg-primary/90 disabled:opacity-60">
                            {{ form.processing ? 'Saving…' : formMode === 'edit' ? 'Save changes' : 'Add customer' }}
                        </button>
                    </div>
                </form>
            </SheetContent>
        </Sheet>
    </AppLayout>
</template>
