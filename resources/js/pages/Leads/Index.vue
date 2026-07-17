<script setup lang="ts">
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import {
    CalendarClock,
    Check,
    CheckCircle2,
    ChevronDown,
    Mail,
    Pencil,
    Phone,
    Plus,
    Search,
    Trash2,
    TrendingUp,
    UserPlus,
    UserRoundCheck,
} from 'lucide-vue-next';
import { computed, ref } from 'vue';
import ModalHost from '@/components/ModalHost.vue';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import {
    Sheet,
    SheetContent,
    SheetDescription,
    SheetHeader,
    SheetTitle,
} from '@/components/ui/sheet';
import { useModals } from '@/composables/useModals';
import AppLayout from '@/layouts/AppLayout.vue';
import {
    type BreadcrumbItem,
    type Lead,
    type LeadFormFields,
    type LeadSourceOption,
    type LeadStatus,
    type LeadStatusOption,
} from '@/types';

interface PaginatedLeads {
    data: Lead[];
    current_page: number;
    last_page: number;
    total: number;
    per_page: number;
    links: { url: string | null; label: string; active: boolean }[];
}

const props = defineProps<{
    leads: PaginatedLeads;
    filters: { filter: string | null };
    statuses: LeadStatusOption[];
    sources: LeadSourceOption[];
    stats: { open: number; due_follow_up: number; won: number; converted: number };
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Leads' },
];

const modals = useModals();

const DUE_FILTER = 'due';

const search = ref('');

const filteredLeads = computed(() => {
    const term = search.value.trim().toLowerCase();
    if (!term) {
        return props.leads.data;
    }
    return props.leads.data.filter((lead) =>
        [lead.name, lead.email, lead.phone, lead.whatsapp_number]
            .filter(Boolean)
            .some((value) => String(value).toLowerCase().includes(term)),
    );
});

const statCards = computed(() => [
    { label: 'Open leads', value: props.stats.open, icon: TrendingUp, iconColor: 'text-sky-500', filter: null },
    { label: 'Follow-up due', value: props.stats.due_follow_up, icon: CalendarClock, iconColor: 'text-amber-500', filter: DUE_FILTER },
    { label: 'Won', value: props.stats.won, icon: CheckCircle2, iconColor: 'text-emerald-500', filter: 'won' },
    { label: 'Converted', value: props.stats.converted, icon: UserRoundCheck, iconColor: 'text-violet-500', filter: null },
]);

const tabs = computed(() => [
    { value: null, label: 'All' },
    { value: DUE_FILTER, label: `Follow-up due${props.stats.due_follow_up ? ` (${props.stats.due_follow_up})` : ''}` },
    ...props.statuses.map((status) => ({ value: status.value as string, label: status.label })),
]);

function applyFilter(filter: string | null) {
    router.get('/leads', filter ? { filter } : {}, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
    });
}

// ── Form drawer (add / edit) ─────────────────────────────────────────
const formOpen = ref(false);
const formMode = ref<'add' | 'edit'>('add');
const editingId = ref<number | null>(null);

/**
 * Resolved from props rather than held as a snapshot, so a status change made
 * while the drawer is open is reflected as soon as the page data refreshes.
 */
const editingLead = computed(
    () => props.leads.data.find((lead) => lead.id === editingId.value) ?? null,
);

const form = useForm<LeadFormFields>({
    name: '',
    phone: '',
    email: '',
    whatsapp_number: '',
    source: 'walk_in',
    follow_up_at: '',
    notes: '',
});

function openAdd() {
    formMode.value = 'add';
    editingId.value = null;
    form.defaults({
        name: '',
        phone: '',
        email: '',
        whatsapp_number: '',
        source: 'walk_in',
        follow_up_at: '',
        notes: '',
    });
    form.reset();
    form.clearErrors();
    formOpen.value = true;
}

/**
 * The index already carries every field the form needs, so editing opens
 * instantly without a detail request.
 */
function openEdit(lead: Lead) {
    formMode.value = 'edit';
    editingId.value = lead.id;
    form.defaults({
        name: lead.name,
        phone: lead.phone ?? '',
        email: lead.email ?? '',
        whatsapp_number: lead.whatsapp_number ?? '',
        source: lead.source,
        follow_up_at: lead.follow_up_at ?? '',
        notes: lead.notes ?? '',
    });
    form.reset();
    form.clearErrors();
    formOpen.value = true;
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
        form.put(`/leads/${editingId.value}`, options);
    } else {
        form.post('/leads', options);
    }
}

/**
 * Status moves on its own endpoint, so it is never changed as a side effect of
 * saving the edit form.
 */
function changeStatus(lead: Lead, status: LeadStatus) {
    if (lead.status === status) {
        return;
    }
    router.patch(
        `/leads/${lead.id}/status`,
        { status },
        {
            preserveScroll: true,
            // The new status can drop the lead out of the active filter, leaving
            // the drawer editing something no longer on the page.
            onSuccess: () => {
                if (formOpen.value && !editingLead.value) {
                    formOpen.value = false;
                }
            },
        },
    );
}

async function convert(lead: Lead) {
    const confirmed = await modals.confirm(
        `${lead.name} will be added to your customers, and this lead marked as won.`,
        { title: 'Convert to customer?', confirmText: 'Convert' },
    );

    if (confirmed) {
        router.post(`/leads/${lead.id}/convert`);
    }
}

async function confirmDelete(lead: Lead) {
    const confirmed = await modals.confirm(`“${lead.name}” will be permanently removed.`, {
        title: 'Delete lead?',
        confirmText: 'Delete',
        danger: true,
    });

    if (confirmed) {
        router.delete(`/leads/${lead.id}`, {
            preserveScroll: true,
            onSuccess: () => {
                formOpen.value = false;
            },
        });
    }
}

function formatFollowUp(date: string) {
    return new Date(`${date}T00:00:00`).toLocaleDateString(undefined, {
        day: 'numeric',
        month: 'short',
        year: 'numeric',
    });
}

const inputClass =
    'w-full rounded-md border border-input bg-background px-3 py-2 text-sm text-foreground placeholder:text-muted-foreground transition focus:border-ring focus:outline-none focus:ring-2 focus:ring-ring/25';
</script>

<template>
    <Head title="Leads" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="p-6">
            <!-- Header -->
            <div class="mb-6 flex flex-wrap items-start justify-between gap-4">
                <div>
                    <h1 class="text-xl font-bold text-foreground">Leads</h1>
                    <p class="text-sm text-muted-foreground">
                        Track prospects through the pipeline and convert them into customers.
                    </p>
                </div>
                <button
                    type="button"
                    class="flex items-center gap-2 rounded-lg bg-primary px-4 py-2.5 text-sm font-medium text-primary-foreground shadow-sm transition hover:bg-primary/90"
                    @click="openAdd"
                >
                    <Plus class="h-4 w-4" />
                    Add Lead
                </button>
            </div>

            <!-- Stats -->
            <div class="mb-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <component
                    :is="card.filter ? 'button' : 'div'"
                    v-for="card in statCards"
                    :key="card.label"
                    :type="card.filter ? 'button' : undefined"
                    class="flex items-center justify-between rounded-lg border border-border bg-card p-4 text-left shadow-sm"
                    :class="[
                        card.filter && 'transition hover:border-primary/50 hover:bg-muted/40',
                        card.filter && filters.filter === card.filter && 'border-primary/60 ring-1 ring-primary/20',
                    ]"
                    @click="card.filter && applyFilter(card.filter)"
                >
                    <div>
                        <p class="text-xs font-medium uppercase tracking-wide text-muted-foreground">
                            {{ card.label }}
                        </p>
                        <p class="mt-1 text-2xl font-bold text-foreground">{{ card.value }}</p>
                    </div>
                    <component :is="card.icon" class="h-5 w-5" :class="card.iconColor" />
                </component>
            </div>

            <!-- Filters -->
            <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
                <div class="flex flex-wrap items-center gap-2">
                    <button
                        v-for="tab in tabs"
                        :key="tab.label"
                        type="button"
                        class="rounded-lg px-3 py-1.5 text-sm font-medium transition"
                        :class="
                            filters.filter === tab.value
                                ? 'bg-primary text-primary-foreground'
                                : 'border border-border bg-card text-muted-foreground hover:text-foreground'
                        "
                        @click="applyFilter(tab.value)"
                    >
                        {{ tab.label }}
                    </button>
                </div>

                <div class="relative">
                    <Search class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                    <input
                        v-model="search"
                        type="search"
                        placeholder="Search leads on this page..."
                        class="w-full rounded-lg border border-border bg-background py-2 pl-9 pr-4 text-sm text-foreground placeholder-muted-foreground focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/20 sm:w-72"
                    />
                </div>
            </div>

            <!-- Empty state -->
            <div
                v-if="filteredLeads.length === 0"
                class="rounded-lg border border-dashed border-border bg-card p-12 text-center"
            >
                <UserPlus class="mx-auto h-8 w-8 text-muted-foreground" />
                <h2 class="mt-3 text-sm font-semibold text-foreground">No leads found</h2>
                <p class="mt-1 text-sm text-muted-foreground">
                    {{
                        search
                            ? 'Try a different search term.'
                            : filters.filter === DUE_FILTER
                              ? 'Nothing needs following up right now.'
                              : 'Add your first lead to start tracking your pipeline.'
                    }}
                </p>
            </div>

            <!-- List -->
            <div v-else class="overflow-hidden rounded-lg border border-border bg-card shadow-sm">
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm">
                        <thead class="border-b border-border bg-muted/40">
                            <tr class="text-xs font-semibold uppercase tracking-wide text-muted-foreground">
                                <th class="px-4 py-3">Lead</th>
                                <th class="px-4 py-3">Contact</th>
                                <th class="px-4 py-3">Source</th>
                                <th class="px-4 py-3">Status</th>
                                <th class="px-4 py-3">Follow-up</th>
                                <th class="px-4 py-3 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-border">
                            <tr v-for="lead in filteredLeads" :key="lead.id" class="transition hover:bg-muted/30">
                                <td class="px-4 py-3">
                                    <button
                                        type="button"
                                        class="font-medium text-foreground transition hover:text-primary"
                                        @click="openEdit(lead)"
                                    >
                                        {{ lead.name }}
                                    </button>
                                    <div v-if="lead.customer" class="mt-0.5 text-xs text-muted-foreground">
                                        Converted &middot;
                                        <Link
                                            :href="`/customers/${lead.customer.id}`"
                                            class="text-primary hover:underline"
                                        >
                                            View customer
                                        </Link>
                                    </div>
                                    <div v-else class="mt-0.5 text-xs text-muted-foreground">
                                        Added {{ lead.created_at }}
                                    </div>
                                </td>
                                <td class="px-4 py-3">
                                    <div v-if="lead.phone" class="flex items-center gap-1.5 text-muted-foreground">
                                        <Phone class="h-3.5 w-3.5" />
                                        {{ lead.phone }}
                                    </div>
                                    <div v-if="lead.email" class="flex items-center gap-1.5 text-muted-foreground">
                                        <Mail class="h-3.5 w-3.5" />
                                        {{ lead.email }}
                                    </div>
                                    <span v-if="!lead.phone && !lead.email" class="text-muted-foreground">&mdash;</span>
                                </td>
                                <td class="px-4 py-3 text-muted-foreground">{{ lead.source_label }}</td>
                                <td class="px-4 py-3">
                                    <DropdownMenu>
                                        <DropdownMenuTrigger as-child>
                                            <button
                                                type="button"
                                                title="Change status"
                                                class="inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-xs font-medium transition hover:opacity-80"
                                                :class="lead.status_badge_classes"
                                            >
                                                {{ lead.status_label }}
                                                <ChevronDown class="h-3 w-3" />
                                            </button>
                                        </DropdownMenuTrigger>
                                        <DropdownMenuContent align="start" class="w-44">
                                            <DropdownMenuLabel>Change status</DropdownMenuLabel>
                                            <DropdownMenuSeparator />
                                            <DropdownMenuItem
                                                v-for="status in statuses"
                                                :key="status.value"
                                                class="cursor-pointer justify-between"
                                                @select="changeStatus(lead, status.value)"
                                            >
                                                {{ status.label }}
                                                <Check v-if="lead.status === status.value" class="h-3.5 w-3.5" />
                                            </DropdownMenuItem>
                                        </DropdownMenuContent>
                                    </DropdownMenu>
                                </td>
                                <td class="px-4 py-3">
                                    <span
                                        v-if="lead.follow_up_at"
                                        class="inline-flex items-center gap-1.5"
                                        :class="lead.follow_up_overdue ? 'font-medium text-destructive' : 'text-muted-foreground'"
                                    >
                                        <CalendarClock class="h-3.5 w-3.5" />
                                        {{ formatFollowUp(lead.follow_up_at) }}
                                    </span>
                                    <span v-else class="text-muted-foreground">&mdash;</span>
                                </td>
                                <td class="px-4 py-3">
                                    <div class="flex items-center justify-end gap-1">
                                        <button
                                            v-if="!lead.is_converted"
                                            type="button"
                                            title="Convert to customer"
                                            class="flex items-center gap-1.5 rounded-lg border border-border px-2.5 py-1.5 text-xs font-medium text-foreground transition hover:bg-muted"
                                            @click="convert(lead)"
                                        >
                                            <UserRoundCheck class="h-3.5 w-3.5" />
                                            Convert
                                        </button>
                                        <button
                                            type="button"
                                            title="Edit lead"
                                            class="rounded-lg p-2 text-muted-foreground transition hover:bg-muted hover:text-foreground"
                                            @click="openEdit(lead)"
                                        >
                                            <Pencil class="h-4 w-4" />
                                        </button>
                                        <button
                                            type="button"
                                            title="Delete lead"
                                            class="rounded-lg p-2 text-muted-foreground transition hover:bg-destructive/10 hover:text-destructive"
                                            @click="confirmDelete(lead)"
                                        >
                                            <Trash2 class="h-4 w-4" />
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Pagination -->
            <div v-if="leads.last_page > 1" class="mt-4 flex flex-wrap items-center justify-center gap-1">
                <Link
                    v-for="link in leads.links"
                    :key="link.label"
                    :href="link.url ?? '#'"
                    preserve-scroll
                    class="rounded-lg px-3 py-1.5 text-sm transition"
                    :class="[
                        link.active
                            ? 'bg-primary text-primary-foreground'
                            : 'text-muted-foreground hover:bg-muted hover:text-foreground',
                        !link.url && 'pointer-events-none opacity-50',
                    ]"
                    v-html="link.label"
                />
            </div>
        </div>

        <!-- Add / edit drawer -->
        <Sheet v-model:open="formOpen">
            <SheetContent class="flex w-full flex-col gap-0 p-0 sm:max-w-xl">
                <SheetHeader class="border-b border-border px-6 py-4 text-left">
                    <SheetTitle>{{ formMode === 'edit' ? 'Edit lead' : 'Add lead' }}</SheetTitle>
                    <SheetDescription>
                        {{
                            formMode === 'edit'
                                ? 'Update this lead’s details and pipeline stage.'
                                : 'Track a new prospect. Convert them into a customer once they buy.'
                        }}
                    </SheetDescription>
                </SheetHeader>

                <form class="flex min-h-0 flex-1 flex-col" @submit.prevent="submit">
                    <div class="flex-1 space-y-5 overflow-y-auto px-6 py-5">
                        <div
                            v-if="editingLead?.customer"
                            class="flex items-center gap-2 rounded-md border border-border bg-muted/40 px-3 py-2.5 text-xs text-muted-foreground"
                        >
                            <UserRoundCheck class="h-4 w-4 shrink-0 text-emerald-500" />
                            <span>
                                Converted to customer
                                <Link
                                    :href="`/customers/${editingLead.customer.id}`"
                                    class="font-medium text-primary hover:underline"
                                >
                                    {{ editingLead.customer.name }}
                                </Link>
                            </span>
                        </div>

                        <div class="space-y-3">
                            <div>
                                <label class="mb-1.5 block text-sm font-medium text-foreground">Full name *</label>
                                <input
                                    v-model="form.name"
                                    type="text"
                                    placeholder="e.g. Alice Johnson"
                                    :class="inputClass"
                                />
                                <p v-if="form.errors.name" class="mt-1 text-xs text-destructive">
                                    {{ form.errors.name }}
                                </p>
                            </div>

                            <div class="grid gap-3 sm:grid-cols-2">
                                <div>
                                    <label class="mb-1.5 block text-sm font-medium text-foreground">Phone</label>
                                    <input
                                        v-model="form.phone"
                                        type="tel"
                                        placeholder="+91 98765 43210"
                                        :class="inputClass"
                                    />
                                    <p v-if="form.errors.phone" class="mt-1 text-xs text-destructive">
                                        {{ form.errors.phone }}
                                    </p>
                                </div>
                                <div>
                                    <label class="mb-1.5 block text-sm font-medium text-foreground">Email</label>
                                    <input
                                        v-model="form.email"
                                        type="email"
                                        placeholder="alice@example.com"
                                        :class="inputClass"
                                    />
                                    <p v-if="form.errors.email" class="mt-1 text-xs text-destructive">
                                        {{ form.errors.email }}
                                    </p>
                                </div>
                            </div>

                            <div>
                                <label class="mb-1.5 block text-sm font-medium text-foreground">WhatsApp number</label>
                                <input
                                    v-model="form.whatsapp_number"
                                    type="tel"
                                    placeholder="+91 98765 43210"
                                    :class="inputClass"
                                />
                            </div>
                        </div>

                        <div class="space-y-3 border-t border-border pt-4">
                            <h3 class="text-sm font-semibold text-foreground">Pipeline</h3>

                            <div class="grid gap-3 sm:grid-cols-2">
                                <div>
                                    <label class="mb-1.5 block text-sm font-medium text-foreground">Source *</label>
                                    <select v-model="form.source" :class="inputClass">
                                        <option v-for="source in sources" :key="source.value" :value="source.value">
                                            {{ source.label }}
                                        </option>
                                    </select>
                                    <p v-if="form.errors.source" class="mt-1 text-xs text-destructive">
                                        {{ form.errors.source }}
                                    </p>
                                </div>
                                <div v-if="editingLead">
                                    <span class="mb-1.5 block text-sm font-medium text-foreground">Status</span>
                                    <div class="flex h-[38px] items-center">
                                        <DropdownMenu>
                                            <DropdownMenuTrigger as-child>
                                                <button
                                                    type="button"
                                                    class="inline-flex items-center gap-1 rounded-full px-2.5 py-1 text-xs font-medium transition hover:opacity-80"
                                                    :class="editingLead.status_badge_classes"
                                                >
                                                    {{ editingLead.status_label }}
                                                    <ChevronDown class="h-3 w-3" />
                                                </button>
                                            </DropdownMenuTrigger>
                                            <DropdownMenuContent align="start" class="w-44">
                                                <DropdownMenuLabel>Change status</DropdownMenuLabel>
                                                <DropdownMenuSeparator />
                                                <DropdownMenuItem
                                                    v-for="status in statuses"
                                                    :key="status.value"
                                                    class="cursor-pointer justify-between"
                                                    @select="changeStatus(editingLead, status.value)"
                                                >
                                                    {{ status.label }}
                                                    <Check
                                                        v-if="editingLead.status === status.value"
                                                        class="h-3.5 w-3.5"
                                                    />
                                                </DropdownMenuItem>
                                            </DropdownMenuContent>
                                        </DropdownMenu>
                                    </div>
                                </div>
                            </div>

                            <div>
                                <label class="mb-1.5 block text-sm font-medium text-foreground">Follow-up date</label>
                                <input v-model="form.follow_up_at" type="date" :class="inputClass" />
                                <p class="mt-1 text-xs text-muted-foreground">
                                    Open leads past this date show under “Follow-up due”.
                                </p>
                                <p v-if="form.errors.follow_up_at" class="mt-1 text-xs text-destructive">
                                    {{ form.errors.follow_up_at }}
                                </p>
                            </div>

                            <div>
                                <label class="mb-1.5 block text-sm font-medium text-foreground">Notes</label>
                                <textarea
                                    v-model="form.notes"
                                    rows="3"
                                    placeholder="What are they interested in?"
                                    :class="inputClass"
                                />
                                <p v-if="form.errors.notes" class="mt-1 text-xs text-destructive">
                                    {{ form.errors.notes }}
                                </p>
                            </div>
                        </div>
                    </div>

                    <div class="flex items-center justify-between gap-3 border-t border-border px-6 py-4">
                        <button
                            v-if="editingLead && !editingLead.is_converted"
                            type="button"
                            class="inline-flex items-center gap-1.5 rounded-md border border-border px-3 py-2 text-sm font-medium text-foreground transition hover:bg-muted"
                            @click="convert(editingLead)"
                        >
                            <UserRoundCheck class="h-4 w-4" />
                            Convert
                        </button>
                        <span v-else />

                        <div class="flex items-center gap-3">
                            <button
                                type="button"
                                class="rounded-md border border-border px-4 py-2 text-sm font-medium text-foreground transition hover:bg-muted"
                                @click="formOpen = false"
                            >
                                Cancel
                            </button>
                            <button
                                type="submit"
                                :disabled="form.processing"
                                class="inline-flex items-center gap-2 rounded-md bg-primary px-5 py-2 text-sm font-medium text-primary-foreground shadow-xs transition hover:bg-primary/90 disabled:opacity-60"
                            >
                                {{ form.processing ? 'Saving…' : formMode === 'edit' ? 'Save changes' : 'Add lead' }}
                            </button>
                        </div>
                    </div>
                </form>
            </SheetContent>
        </Sheet>

        <ModalHost />
    </AppLayout>
</template>
