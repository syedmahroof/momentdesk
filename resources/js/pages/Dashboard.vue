<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import {
    ArrowRight,
    Award,
    Cake,
    CalendarClock,
    CalendarRange,
    Heart,
    Star,
    UsersRound,
} from 'lucide-vue-next';
import { computed, type Component } from 'vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem, type TodayEvent, type UpcomingEvent } from '@/types';
import { dashboard } from '@/routes';

interface EventTypeCount {
    type: string;
    label: string;
    count: number;
}

interface Stats {
    total_customers: number;
    today_events: number;
    upcoming_events: number;
}

const props = defineProps<{
    todayEvents: TodayEvent[];
    upcomingEvents: UpcomingEvent[];
    stats: Stats;
    eventsByType: EventTypeCount[];
}>();

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Dashboard', href: dashboard().url }];

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

const statCards = computed(() => [
    {
        key: 'total_customers',
        label: 'Total customers',
        value: props.stats.total_customers,
        icon: UsersRound,
        tint: 'bg-blue-50 text-blue-600 dark:bg-blue-500/10 dark:text-blue-400',
    },
    {
        key: 'today_events',
        label: "Today's events",
        value: props.stats.today_events,
        icon: CalendarClock,
        tint: 'bg-sky-50 text-sky-600 dark:bg-sky-500/10 dark:text-sky-400',
    },
    {
        key: 'upcoming_events',
        label: 'Upcoming (7 days)',
        value: props.stats.upcoming_events,
        icon: CalendarRange,
        tint: 'bg-indigo-50 text-indigo-600 dark:bg-indigo-500/10 dark:text-indigo-400',
    },
]);

// Events-by-type — fixed categorical order, always paired with a label.
const typeBarColor: Record<string, string> = {
    birthday: 'bg-blue-500',
    wedding: 'bg-violet-500',
    work: 'bg-amber-500',
    custom: 'bg-emerald-500',
};
const maxType = computed(() => Math.max(1, ...props.eventsByType.map((t) => t.count)));
const totalTracked = computed(() => props.eventsByType.reduce((sum, t) => sum + t.count, 0));
</script>

<template>
    <Head title="Dashboard" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="mx-auto flex w-full max-w-7xl flex-col gap-6 p-4 sm:p-6 lg:p-8">
            <!-- Page header -->
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h1 class="text-2xl font-semibold tracking-tight text-foreground">Analytics overview</h1>
                    <p class="text-sm text-muted-foreground">Track engagement across every customer moment.</p>
                </div>
            </div>

            <!-- KPIs -->
            <div class="grid grid-cols-2 gap-4 lg:grid-cols-3">
                <div
                    v-for="card in statCards"
                    :key="card.key"
                    class="rounded-lg border border-border bg-card p-5 shadow-xs"
                >
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-medium uppercase tracking-wide text-muted-foreground">
                            {{ card.label }}
                        </span>
                        <span :class="['flex h-9 w-9 shrink-0 items-center justify-center rounded-md', card.tint]">
                            <component :is="card.icon" class="h-5 w-5" />
                        </span>
                    </div>
                    <p class="mt-4 text-3xl font-semibold tracking-tight tabular-nums text-foreground">
                        {{ card.value }}
                    </p>
                </div>
            </div>

            <!-- Events by type -->
            <section class="rounded-lg border border-border bg-card shadow-xs">
                <header class="flex items-center justify-between border-b border-border px-6 py-4">
                    <div>
                        <h2 class="text-sm font-semibold text-foreground">Events by type</h2>
                        <p class="text-xs text-muted-foreground">Tracked dates</p>
                    </div>
                    <span class="text-sm font-semibold tabular-nums text-foreground">{{ totalTracked }}</span>
                </header>
                <div class="flex flex-col gap-4 px-6 py-6">
                    <div v-for="item in eventsByType" :key="item.type">
                        <div class="mb-1.5 flex items-center justify-between text-sm">
                            <span class="flex items-center gap-2 text-foreground">
                                <component :is="eventTypeIcon[item.type] ?? Star" :class="['h-4 w-4', eventTypeIconColor[item.type]]" />
                                {{ item.label }}
                            </span>
                            <span class="font-medium tabular-nums text-muted-foreground">{{ item.count }}</span>
                        </div>
                        <div class="h-2 overflow-hidden rounded-full bg-muted">
                            <div
                                :class="['h-full rounded-full transition-all', typeBarColor[item.type]]"
                                :style="{ width: `${(item.count / maxType) * 100}%` }"
                            ></div>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Event lists -->
            <div class="grid gap-4 lg:grid-cols-2">
                <!-- Today's Events -->
                <section class="rounded-lg border border-border bg-card shadow-xs">
                    <header class="flex items-center justify-between gap-4 border-b border-border px-6 py-4">
                        <div class="flex items-center gap-2.5">
                            <CalendarClock class="h-4 w-4 text-muted-foreground" />
                            <h2 class="text-sm font-semibold text-foreground">Today's events</h2>
                            <span
                                v-if="todayEvents.length"
                                class="inline-flex h-5 min-w-5 items-center justify-center rounded-full bg-primary/10 px-1.5 text-xs font-semibold text-primary"
                            >
                                {{ todayEvents.length }}
                            </span>
                        </div>
                    </header>

                    <div v-if="!todayEvents.length" class="px-6 py-12 text-center">
                        <p class="text-sm font-medium text-foreground">Nothing scheduled today</p>
                        <p class="mt-0.5 text-sm text-muted-foreground">Enjoy your day.</p>
                    </div>

                    <ul v-else class="divide-y divide-border">
                        <li
                            v-for="event in todayEvents"
                            :key="event.id"
                            class="flex items-center justify-between gap-4 px-6 py-3 transition hover:bg-muted/40"
                        >
                            <div class="flex min-w-0 items-center gap-3">
                                <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-muted">
                                    <component :is="eventTypeIcon[event.type] ?? Star" :class="['h-4 w-4', eventTypeIconColor[event.type]]" />
                                </span>
                                <div class="min-w-0">
                                    <p class="truncate text-sm font-medium text-foreground">{{ event.customer_name }}</p>
                                    <p class="truncate text-sm text-muted-foreground">
                                        {{ event.display_title }}
                                        <span v-if="event.years > 0" class="opacity-70">· {{ event.ordinal_years }}</span>
                                    </p>
                                </div>
                            </div>
                            <Link
                                :href="`/customers/${event.customer_id}/dates/${event.id}/send`"
                                class="inline-flex shrink-0 items-center gap-1.5 rounded-md border border-border bg-background px-3 py-1.5 text-xs font-medium text-foreground transition hover:bg-muted"
                            >
                                <Send class="h-3.5 w-3.5" />
                                Send
                            </Link>
                        </li>
                    </ul>
                </section>

                <!-- Upcoming Events -->
                <section class="rounded-lg border border-border bg-card shadow-xs">
                    <header class="flex items-center gap-2.5 border-b border-border px-6 py-4">
                        <CalendarRange class="h-4 w-4 text-muted-foreground" />
                        <h2 class="text-sm font-semibold text-foreground">Upcoming</h2>
                        <span class="text-xs text-muted-foreground">· next 7 days</span>
                    </header>

                    <div v-if="!upcomingEvents.length" class="px-6 py-12 text-center">
                        <p class="text-sm font-medium text-foreground">No upcoming events</p>
                        <p class="mt-0.5 text-sm text-muted-foreground">Nothing in the next 7 days.</p>
                    </div>

                    <ul v-else class="divide-y divide-border">
                        <li
                            v-for="event in upcomingEvents"
                            :key="event.id"
                            class="flex items-center justify-between gap-4 px-6 py-3 transition hover:bg-muted/40"
                        >
                            <div class="flex min-w-0 items-center gap-3">
                                <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-muted">
                                    <component :is="eventTypeIcon[event.type] ?? Star" :class="['h-4 w-4', eventTypeIconColor[event.type]]" />
                                </span>
                                <div class="min-w-0">
                                    <p class="truncate text-sm font-medium text-foreground">{{ event.customer_name }}</p>
                                    <p class="truncate text-sm text-muted-foreground">{{ event.display_title }}</p>
                                </div>
                            </div>
                            <div class="flex shrink-0 items-center gap-3">
                                <span class="rounded-full bg-muted px-2.5 py-0.5 text-xs font-medium text-muted-foreground">
                                    in {{ event.days_until }}d
                                </span>
                                <Link
                                    :href="`/customers/${event.customer_id}/dates/${event.id}/send`"
                                    class="inline-flex items-center gap-0.5 text-xs font-medium text-primary transition hover:opacity-80"
                                >
                                    Send
                                    <ArrowRight class="h-3.5 w-3.5" />
                                </Link>
                            </div>
                        </li>
                    </ul>
                </section>
            </div>
        </div>
    </AppLayout>
</template>
