<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import {
    Calendar,
    CalendarDays,
    MessageSquareHeart,
    Send,
    SendHorizonal,
    Users,
} from 'lucide-vue-next';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem, type DashboardStats, type TodayEvent, type UpcomingEvent } from '@/types';
import { dashboard } from '@/routes';

const props = defineProps<{
    todayEvents: TodayEvent[];
    upcomingEvents: UpcomingEvent[];
    stats: DashboardStats;
}>();

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Dashboard', href: dashboard().url }];

const eventTypeEmoji: Record<string, string> = {
    birthday: '🎂',
    wedding: '💍',
    work: '🏆',
    custom: '🌟',
};

const eventTypeColor: Record<string, string> = {
    birthday: 'bg-pink-50 text-pink-700 border-pink-200',
    wedding: 'bg-violet-50 text-violet-700 border-violet-200',
    work: 'bg-amber-50 text-amber-700 border-amber-200',
    custom: 'bg-sky-50 text-sky-700 border-sky-200',
};

function bulkSendToday() {
    router.post('/wishes/bulk-today');
}
</script>

<template>
    <Head title="Dashboard" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-6 p-6">
            <!-- Stats -->
            <div class="grid grid-cols-2 gap-4 md:grid-cols-4">
                <div class="rounded-2xl border border-border bg-card p-5 shadow-sm">
                    <div class="flex items-center gap-3">
                        <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-primary/10">
                            <Users class="h-5 w-5 text-primary" />
                        </div>
                        <div>
                            <p class="text-2xl font-bold text-foreground">{{ stats.total_customers }}</p>
                            <p class="text-xs text-muted-foreground">Total Customers</p>
                        </div>
                    </div>
                </div>

                <div class="rounded-2xl border border-border bg-card p-5 shadow-sm">
                    <div class="flex items-center gap-3">
                        <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-pink-100">
                            <CalendarDays class="h-5 w-5 text-pink-600" />
                        </div>
                        <div>
                            <p class="text-2xl font-bold text-foreground">{{ stats.today_events }}</p>
                            <p class="text-xs text-muted-foreground">Today's Events</p>
                        </div>
                    </div>
                </div>

                <div class="rounded-2xl border border-border bg-card p-5 shadow-sm">
                    <div class="flex items-center gap-3">
                        <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-violet-100">
                            <Calendar class="h-5 w-5 text-violet-600" />
                        </div>
                        <div>
                            <p class="text-2xl font-bold text-foreground">{{ stats.upcoming_events }}</p>
                            <p class="text-xs text-muted-foreground">Upcoming (7 days)</p>
                        </div>
                    </div>
                </div>

                <div class="rounded-2xl border border-border bg-card p-5 shadow-sm">
                    <div class="flex items-center gap-3">
                        <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-green-100">
                            <MessageSquareHeart class="h-5 w-5 text-green-600" />
                        </div>
                        <div>
                            <p class="text-2xl font-bold text-foreground">{{ stats.sent_this_month }}</p>
                            <p class="text-xs text-muted-foreground">Sent This Month</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Today's Events -->
            <div class="rounded-2xl border border-border bg-card shadow-sm">
                <div class="flex items-center justify-between border-b border-border p-5">
                    <div class="flex items-center gap-2">
                        <span class="text-xl">🎉</span>
                        <h2 class="text-base font-semibold text-foreground">Today's Events</h2>
                        <span
                            v-if="todayEvents.length"
                            class="rounded-full bg-primary/10 px-2 py-0.5 text-xs font-medium text-primary"
                        >
                            {{ todayEvents.length }}
                        </span>
                    </div>
                    <button
                        v-if="todayEvents.length"
                        class="flex items-center gap-1.5 rounded-xl bg-primary px-4 py-2 text-sm font-medium text-primary-foreground shadow-sm transition hover:bg-primary/90"
                        @click="bulkSendToday"
                    >
                        <SendHorizonal class="h-4 w-4" />
                        Send All
                    </button>
                </div>

                <div v-if="!todayEvents.length" class="p-10 text-center text-muted-foreground">
                    <CalendarDays class="mx-auto mb-3 h-10 w-10 opacity-30" />
                    <p class="text-sm">No events today. Enjoy your day!</p>
                </div>

                <div v-else class="divide-y divide-border">
                    <div
                        v-for="event in todayEvents"
                        :key="event.id"
                        class="flex items-center justify-between gap-4 p-4"
                    >
                        <div class="flex items-center gap-3">
                            <span class="text-2xl">{{ eventTypeEmoji[event.type] ?? '🌟' }}</span>
                            <div>
                                <p class="font-medium text-foreground">{{ event.customer_name }}</p>
                                <p class="text-sm text-muted-foreground">
                                    {{ event.display_title }}
                                    <span v-if="event.years > 0" class="ml-1 opacity-70">
                                        · {{ event.ordinal_years }}
                                    </span>
                                </p>
                            </div>
                        </div>
                        <div class="flex items-center gap-2">
                            <a
                                v-if="event.whatsapp_number"
                                :href="`https://wa.me/${event.whatsapp_number.replace(/\D/g, '')}?text=${encodeURIComponent(`Happy ${event.display_title}, ${event.customer_name}!`)}`"
                                target="_blank"
                                class="flex items-center gap-1.5 rounded-lg border border-green-200 bg-green-50 px-3 py-1.5 text-xs font-medium text-green-700 transition hover:bg-green-100"
                            >
                                WhatsApp
                            </a>
                            <Link
                                :href="`/customers/${event.customer_id}/dates/${event.id}/send`"
                                class="flex items-center gap-1.5 rounded-lg border border-border bg-background px-3 py-1.5 text-xs font-medium text-foreground transition hover:bg-muted"
                            >
                                <Send class="h-3.5 w-3.5" />
                                Send Wish
                            </Link>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Upcoming Events -->
            <div class="rounded-2xl border border-border bg-card shadow-sm">
                <div class="border-b border-border p-5">
                    <h2 class="text-base font-semibold text-foreground">Upcoming Events</h2>
                    <p class="text-xs text-muted-foreground">Next 7 days</p>
                </div>

                <div v-if="!upcomingEvents.length" class="p-10 text-center text-muted-foreground">
                    <Calendar class="mx-auto mb-3 h-10 w-10 opacity-30" />
                    <p class="text-sm">No upcoming events in the next 7 days.</p>
                </div>

                <div v-else class="divide-y divide-border">
                    <div
                        v-for="event in upcomingEvents"
                        :key="event.id"
                        class="flex items-center justify-between gap-4 p-4"
                    >
                        <div class="flex items-center gap-3">
                            <span class="text-xl">{{ eventTypeEmoji[event.type] ?? '🌟' }}</span>
                            <div>
                                <p class="font-medium text-foreground">{{ event.customer_name }}</p>
                                <p class="text-sm text-muted-foreground">{{ event.display_title }}</p>
                            </div>
                        </div>
                        <div class="flex items-center gap-3">
                            <span
                                :class="['rounded-full border px-2.5 py-0.5 text-xs font-medium', eventTypeColor[event.type] ?? 'bg-muted text-muted-foreground']"
                            >
                                in {{ event.days_until }} day{{ event.days_until === 1 ? '' : 's' }}
                            </span>
                            <Link
                                :href="`/customers/${event.customer_id}/dates/${event.id}/send`"
                                class="text-xs text-primary underline-offset-2 hover:underline"
                            >
                                Send Wish
                            </Link>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
