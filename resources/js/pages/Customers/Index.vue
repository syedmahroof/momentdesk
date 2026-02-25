<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { Mail, MessageCircle, Phone, Plus, Search, Trash2 } from 'lucide-vue-next';
import { ref } from 'vue';
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

defineProps<{ customers: PaginatedCustomers }>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Customers' },
];

const search = ref('');

const eventTypeEmoji: Record<string, string> = {
    birthday: '🎂',
    wedding: '💍',
    work: '🏆',
    custom: '🌟',
};

function confirmDelete(customer: Customer) {
    if (confirm(`Delete ${customer.name}? This cannot be undone.`)) {
        router.delete(`/customers/${customer.id}`);
    }
}
</script>

<template>
    <Head title="Customers" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-6 p-6">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-xl font-bold text-foreground">Customers</h1>
                    <p class="text-sm text-muted-foreground">{{ customers.total }} total customers</p>
                </div>
                <Link
                    href="/customers/create"
                    class="flex items-center gap-2 rounded-xl bg-primary px-4 py-2.5 text-sm font-medium text-primary-foreground shadow-sm transition hover:bg-primary/90"
                >
                    <Plus class="h-4 w-4" />
                    Add Customer
                </Link>
            </div>

            <!-- Empty state -->
            <div v-if="!customers.data.length" class="rounded-2xl border border-dashed border-border p-16 text-center">
                <div class="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-2xl bg-muted">
                    <span class="text-3xl">👥</span>
                </div>
                <h3 class="mb-1 font-semibold text-foreground">No customers yet</h3>
                <p class="mb-6 text-sm text-muted-foreground">Add your first customer to start sending wishes.</p>
                <Link href="/customers/create" class="rounded-xl bg-primary px-5 py-2.5 text-sm font-medium text-primary-foreground">
                    Add Customer
                </Link>
            </div>

            <!-- Customer list -->
            <div v-else class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                <div
                    v-for="customer in customers.data"
                    :key="customer.id"
                    class="group rounded-2xl border border-border bg-card p-5 shadow-sm transition hover:shadow-md"
                >
                    <div class="mb-3 flex items-start justify-between">
                        <div class="flex items-center gap-3">
                            <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-primary/10 text-base font-semibold text-primary">
                                {{ customer.name.charAt(0).toUpperCase() }}
                            </div>
                            <div>
                                <Link :href="`/customers/${customer.id}`" class="font-semibold text-foreground hover:text-primary">
                                    {{ customer.name }}
                                </Link>
                                <p class="text-xs text-muted-foreground">{{ customer.created_at }}</p>
                            </div>
                        </div>
                        <button
                            class="rounded-lg p-1.5 text-muted-foreground opacity-0 transition hover:bg-destructive/10 hover:text-destructive group-hover:opacity-100"
                            @click="confirmDelete(customer)"
                        >
                            <Trash2 class="h-4 w-4" />
                        </button>
                    </div>

                    <!-- Contact info -->
                    <div class="mb-3 flex flex-col gap-1">
                        <div v-if="customer.phone" class="flex items-center gap-1.5 text-xs text-muted-foreground">
                            <Phone class="h-3.5 w-3.5" />
                            {{ customer.phone }}
                        </div>
                        <div v-if="customer.email" class="flex items-center gap-1.5 text-xs text-muted-foreground">
                            <Mail class="h-3.5 w-3.5" />
                            {{ customer.email }}
                        </div>
                        <div v-if="customer.whatsapp_number" class="flex items-center gap-1.5 text-xs text-green-600">
                            <MessageCircle class="h-3.5 w-3.5" />
                            WhatsApp: {{ customer.whatsapp_number }}
                        </div>
                    </div>

                    <!-- Upcoming event badge -->
                    <div v-if="customer.upcoming_event" class="mb-3 rounded-lg bg-muted px-3 py-2">
                        <p class="text-xs text-muted-foreground">
                            {{ eventTypeEmoji[customer.upcoming_event.type] ?? '🌟' }}
                            {{ customer.upcoming_event.display_title }}
                            <span class="font-medium text-foreground">
                                in {{ customer.upcoming_event.days_until }} day{{ customer.upcoming_event.days_until === 1 ? '' : 's' }}
                            </span>
                        </p>
                    </div>

                    <!-- Footer -->
                    <div class="flex items-center justify-between">
                        <span class="text-xs text-muted-foreground">{{ customer.dates_count }} event{{ customer.dates_count === 1 ? '' : 's' }}</span>
                        <div class="flex gap-2">
                            <Link
                                :href="`/customers/${customer.id}/edit`"
                                class="text-xs text-primary underline-offset-2 hover:underline"
                            >
                                Edit
                            </Link>
                            <Link
                                :href="`/customers/${customer.id}`"
                                class="text-xs text-muted-foreground underline-offset-2 hover:underline"
                            >
                                View
                            </Link>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Pagination -->
            <div v-if="customers.last_page > 1" class="flex items-center justify-center gap-1">
                <template v-for="link in customers.links" :key="link.label">
                    <Link
                        v-if="link.url"
                        :href="link.url"
                        :class="[
                            'rounded-lg px-3.5 py-2 text-sm transition',
                            link.active
                                ? 'bg-primary text-primary-foreground'
                                : 'text-muted-foreground hover:bg-muted',
                        ]"
                        v-html="link.label"
                    />
                    <span
                        v-else
                        class="rounded-lg px-3.5 py-2 text-sm text-muted-foreground opacity-40"
                        v-html="link.label"
                    />
                </template>
            </div>
        </div>
    </AppLayout>
</template>
