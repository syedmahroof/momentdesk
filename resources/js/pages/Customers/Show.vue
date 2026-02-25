<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { CalendarDays, Mail, MessageCircle, Pencil, Phone, Send, Trash2 } from 'lucide-vue-next';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem, type Customer, type CustomerDate, type MessageLog } from '@/types';

const props = defineProps<{
    customer: Customer & {
        dates: CustomerDate[];
        message_logs: MessageLog[];
    };
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Customers', href: '/customers' },
    { title: props.customer.name },
];

const eventTypeEmoji: Record<string, string> = {
    birthday: '🎂',
    wedding: '💍',
    work: '🏆',
    custom: '🌟',
};

const statusColor: Record<string, string> = {
    sent: 'text-green-600 bg-green-50',
    failed: 'text-red-600 bg-red-50',
    pending: 'text-amber-600 bg-amber-50',
    delivered: 'text-blue-600 bg-blue-50',
};

function confirmDelete() {
    if (confirm(`Delete ${props.customer.name}? This cannot be undone.`)) {
        router.delete(`/customers/${props.customer.id}`);
    }
}
</script>

<template>
    <Head :title="customer.name" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="p-6">
            <div class="mx-auto max-w-3xl space-y-6">
                <!-- Profile header -->
                <div class="rounded-2xl border border-border bg-card p-6 shadow-sm">
                    <div class="flex items-start justify-between">
                        <div class="flex items-center gap-4">
                            <div class="flex h-14 w-14 items-center justify-center rounded-2xl bg-primary/10 text-xl font-bold text-primary">
                                {{ customer.name.charAt(0).toUpperCase() }}
                            </div>
                            <div>
                                <h1 class="text-xl font-bold text-foreground">{{ customer.name }}</h1>
                                <div class="mt-1 flex flex-wrap gap-3">
                                    <span v-if="customer.phone" class="flex items-center gap-1 text-sm text-muted-foreground">
                                        <Phone class="h-3.5 w-3.5" /> {{ customer.phone }}
                                    </span>
                                    <span v-if="customer.email" class="flex items-center gap-1 text-sm text-muted-foreground">
                                        <Mail class="h-3.5 w-3.5" /> {{ customer.email }}
                                    </span>
                                    <span v-if="customer.whatsapp_number" class="flex items-center gap-1 text-sm text-green-600">
                                        <MessageCircle class="h-3.5 w-3.5" /> {{ customer.whatsapp_number }}
                                    </span>
                                </div>
                                <p v-if="customer.notes" class="mt-2 text-sm text-muted-foreground">{{ customer.notes }}</p>
                            </div>
                        </div>
                        <div class="flex gap-2">
                            <Link
                                :href="`/customers/${customer.id}/edit`"
                                class="flex items-center gap-1.5 rounded-lg border border-border px-3 py-1.5 text-sm text-foreground transition hover:bg-muted"
                            >
                                <Pencil class="h-4 w-4" />
                                Edit
                            </Link>
                            <button
                                class="flex items-center gap-1.5 rounded-lg border border-destructive/30 px-3 py-1.5 text-sm text-destructive transition hover:bg-destructive/10"
                                @click="confirmDelete"
                            >
                                <Trash2 class="h-4 w-4" />
                                Delete
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Important dates -->
                <div class="rounded-2xl border border-border bg-card shadow-sm">
                    <div class="border-b border-border p-5">
                        <h2 class="font-semibold text-foreground">Important Dates</h2>
                    </div>
                    <div v-if="!customer.dates?.length" class="p-10 text-center text-sm text-muted-foreground">
                        No dates added yet.
                        <Link :href="`/customers/${customer.id}/edit`" class="ml-1 text-primary hover:underline">Add one</Link>
                    </div>
                    <div v-else class="divide-y divide-border">
                        <div
                            v-for="date in customer.dates"
                            :key="date.id"
                            class="flex items-center justify-between p-4"
                        >
                            <div class="flex items-center gap-3">
                                <span class="text-2xl">{{ eventTypeEmoji[date.type] ?? '🌟' }}</span>
                                <div>
                                    <p class="font-medium text-foreground">{{ date.display_title }}</p>
                                    <p class="text-sm text-muted-foreground">
                                        {{ date.date }}
                                        <span v-if="date.years > 0" class="ml-1">· {{ date.ordinal_years }}</span>
                                    </p>
                                </div>
                            </div>
                            <div class="flex items-center gap-3">
                                <span
                                    v-if="date.days_until <= 7"
                                    class="rounded-full bg-pink-50 px-2.5 py-0.5 text-xs font-medium text-pink-700"
                                >
                                    {{ date.days_until === 0 ? 'Today!' : `in ${date.days_until} days` }}
                                </span>
                                <Link
                                    :href="`/customers/${customer.id}/dates/${date.id}/send`"
                                    class="flex items-center gap-1.5 rounded-lg bg-primary px-3 py-1.5 text-xs font-medium text-primary-foreground transition hover:bg-primary/90"
                                >
                                    <Send class="h-3.5 w-3.5" />
                                    Send Wish
                                </Link>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Message history -->
                <div v-if="customer.message_logs?.length" class="rounded-2xl border border-border bg-card shadow-sm">
                    <div class="border-b border-border p-5">
                        <h2 class="font-semibold text-foreground">Message History</h2>
                    </div>
                    <div class="divide-y divide-border">
                        <div
                            v-for="log in customer.message_logs"
                            :key="log.id"
                            class="flex items-start justify-between gap-4 p-4"
                        >
                            <div class="flex-1">
                                <p class="line-clamp-2 text-sm text-foreground">{{ log.message }}</p>
                                <p class="mt-0.5 text-xs text-muted-foreground">
                                    {{ log.channel.toUpperCase() }}
                                    <span v-if="log.sent_at"> · {{ log.sent_at }}</span>
                                </p>
                            </div>
                            <span :class="['rounded-full px-2.5 py-0.5 text-xs font-medium', statusColor[log.status]]">
                                {{ log.status }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
