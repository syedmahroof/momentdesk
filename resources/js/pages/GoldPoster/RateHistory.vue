<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { RefreshCw, TrendingDown, TrendingUp } from 'lucide-vue-next';
import { computed } from 'vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';

interface RateData { date: string; price_22k_1g: number; price_22k_8g: number; price_18k_1g: number }

const props = defineProps<{ rates: RateData[] }>();

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Rate history', href: '/gold-poster/rate-history' }];

function fmt(n: number) { return `₹${Number(n).toLocaleString('en-IN')}`; }
function shortDate(iso: string) { try { return new Date(`${iso}T00:00:00`).toLocaleDateString('en-GB', { day: '2-digit', month: 'short' }); } catch { return iso; } }
function displayDate(iso: string) {
    try { return new Date(`${iso}T00:00:00`).toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' }); } catch { return iso; }
}

const history = computed(() => props.rates ?? []);

const tableRows = computed(() =>
    history.value
        .map((r, i) => ({ ...r, change: i > 0 ? r.price_22k_1g - history.value[i - 1].price_22k_1g : null }))
        .slice()
        .reverse(),
);

const latest = computed(() => tableRows.value[0] ?? null);

const CW = 640, CH = 240, PL = 64, PR = 16, PT = 16, PB = 30;
const chart = computed(() => {
    const d = history.value.filter((r) => r.price_22k_1g > 0);
    if (d.length < 2) return null;
    const vals = d.map((r) => r.price_22k_1g);
    let min = Math.min(...vals); let max = Math.max(...vals);
    if (min === max) { min -= 10; max += 10; }
    const iw = CW - PL - PR; const ih = CH - PT - PB;
    const pts = d.map((r, i) => ({
        x: PL + (i / (d.length - 1)) * iw,
        y: PT + (1 - (r.price_22k_1g - min) / (max - min)) * ih,
        val: r.price_22k_1g, date: r.date,
    }));
    const line = pts.map((p, i) => `${i ? 'L' : 'M'}${p.x.toFixed(1)},${p.y.toFixed(1)}`).join(' ');
    const area = `${line} L${pts[pts.length - 1].x.toFixed(1)},${(PT + ih).toFixed(1)} L${pts[0].x.toFixed(1)},${(PT + ih).toFixed(1)} Z`;
    return { pts, line, area, min, max, baseY: PT + ih, first: d[0], last: d[d.length - 1] };
});
</script>

<template>
    <Head title="Rate history" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="mx-auto flex w-full max-w-5xl flex-col gap-6 p-4 sm:p-6 lg:p-8">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h1 class="text-2xl font-semibold tracking-tight text-foreground">Rate history</h1>
                    <p class="text-sm text-muted-foreground">Every gold rate you've saved, with the 1 gram 22K trend.</p>
                </div>
                <Link href="/gold-poster/update" class="inline-flex shrink-0 items-center gap-2 rounded-md bg-primary px-4 py-2.5 text-sm font-medium text-primary-foreground shadow-xs transition hover:bg-primary/90">
                    <RefreshCw class="h-4 w-4" /> Update today's rate
                </Link>
            </div>

            <div v-if="!history.length" class="rounded-lg border border-dashed border-border bg-card px-6 py-14 text-center">
                <p class="text-sm font-medium text-foreground">No rates saved yet</p>
                <p class="mx-auto mt-1 max-w-sm text-sm text-muted-foreground">Save today's rate from the update screen and it will start building up here.</p>
            </div>

            <template v-else>
                <!-- Latest snapshot -->
                <div v-if="latest" class="grid grid-cols-3 gap-4">
                    <div class="rounded-lg border border-border bg-card p-5 shadow-xs">
                        <p class="text-xs font-medium uppercase tracking-wide text-muted-foreground">22K · 1g</p>
                        <p class="mt-2 text-2xl font-semibold tabular-nums text-foreground">{{ fmt(latest.price_22k_1g) }}</p>
                    </div>
                    <div class="rounded-lg border border-border bg-card p-5 shadow-xs">
                        <p class="text-xs font-medium uppercase tracking-wide text-muted-foreground">22K · 8g</p>
                        <p class="mt-2 text-2xl font-semibold tabular-nums text-foreground">{{ fmt(latest.price_22k_8g) }}</p>
                    </div>
                    <div class="rounded-lg border border-border bg-card p-5 shadow-xs">
                        <p class="text-xs font-medium uppercase tracking-wide text-muted-foreground">18K · 1g</p>
                        <p class="mt-2 text-2xl font-semibold tabular-nums text-foreground">{{ fmt(latest.price_18k_1g) }}</p>
                    </div>
                </div>

                <!-- Trend chart -->
                <section class="rounded-lg border border-border bg-card p-4 shadow-xs sm:p-5">
                    <h2 class="text-sm font-semibold text-foreground">1 gram 22K trend</h2>
                    <p class="mb-4 text-xs text-muted-foreground">Saved daily rates over time.</p>
                    <div v-if="chart">
                        <svg :viewBox="`0 0 ${CW} ${CH}`" class="w-full" role="img" aria-label="Gold rate trend">
                            <line :x1="PL" :y1="PT" :x2="CW - PR" :y2="PT" class="stroke-border" stroke-width="1" />
                            <line :x1="PL" :y1="chart.baseY" :x2="CW - PR" :y2="chart.baseY" class="stroke-border" stroke-width="1" />
                            <path :d="chart.area" class="fill-primary" fill-opacity="0.1" />
                            <path :d="chart.line" fill="none" class="stroke-primary" stroke-width="2.5" stroke-linejoin="round" stroke-linecap="round" />
                            <circle v-for="(p, i) in chart.pts" :key="i" :cx="p.x" :cy="p.y" r="4" class="fill-primary">
                                <title>{{ shortDate(p.date) }} · {{ fmt(p.val) }}</title>
                            </circle>
                            <text :x="PL - 8" :y="PT + 4" text-anchor="end" class="fill-muted-foreground" font-size="12">{{ fmt(chart.max) }}</text>
                            <text :x="PL - 8" :y="chart.baseY + 4" text-anchor="end" class="fill-muted-foreground" font-size="12">{{ fmt(chart.min) }}</text>
                            <text :x="PL" :y="CH - 8" text-anchor="start" class="fill-muted-foreground" font-size="12">{{ shortDate(chart.first.date) }}</text>
                            <text :x="CW - PR" :y="CH - 8" text-anchor="end" class="fill-muted-foreground" font-size="12">{{ shortDate(chart.last.date) }}</text>
                        </svg>
                    </div>
                    <p v-else class="py-8 text-center text-sm text-muted-foreground">Save at least two days of rates to see the trend.</p>
                </section>

                <!-- History table -->
                <section class="overflow-hidden rounded-lg border border-border bg-card shadow-xs">
                    <div class="flex items-center justify-between border-b border-border px-4 py-3 sm:px-5 sm:py-4">
                        <h2 class="text-sm font-semibold text-foreground">All saved rates</h2>
                        <span class="text-xs text-muted-foreground">{{ history.length }} day{{ history.length === 1 ? '' : 's' }}</span>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full whitespace-nowrap text-left text-sm">
                            <thead>
                                <tr class="border-b border-border bg-muted/40 text-xs uppercase tracking-wide text-muted-foreground">
                                    <th class="px-3 py-3 font-medium sm:px-5">Date</th>
                                    <th class="px-3 py-3 text-right font-medium sm:px-5">22K · 1g</th>
                                    <th class="px-3 py-3 text-right font-medium sm:px-5">22K · 8g</th>
                                    <th class="px-3 py-3 text-right font-medium sm:px-5">18K · 1g</th>
                                    <th class="px-3 py-3 text-right font-medium sm:px-5">Change</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-border">
                                <tr v-for="r in tableRows" :key="r.date" class="transition hover:bg-muted/40">
                                    <td class="px-3 py-2.5 text-foreground sm:px-5">{{ displayDate(r.date) }}</td>
                                    <td class="px-3 py-2.5 text-right font-medium tabular-nums text-foreground sm:px-5">{{ fmt(r.price_22k_1g) }}</td>
                                    <td class="px-3 py-2.5 text-right tabular-nums text-muted-foreground sm:px-5">{{ fmt(r.price_22k_8g) }}</td>
                                    <td class="px-3 py-2.5 text-right tabular-nums text-muted-foreground sm:px-5">{{ fmt(r.price_18k_1g) }}</td>
                                    <td class="px-3 py-2.5 text-right tabular-nums sm:px-5">
                                        <span v-if="r.change === null" class="text-muted-foreground">—</span>
                                        <span v-else-if="r.change > 0" class="inline-flex items-center justify-end gap-0.5 text-emerald-600 dark:text-emerald-400"><TrendingUp class="h-3.5 w-3.5" />{{ fmt(r.change) }}</span>
                                        <span v-else-if="r.change < 0" class="inline-flex items-center justify-end gap-0.5 text-rose-600 dark:text-rose-400"><TrendingDown class="h-3.5 w-3.5" />{{ fmt(Math.abs(r.change)) }}</span>
                                        <span v-else class="text-muted-foreground">₹0</span>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </section>
            </template>
        </div>
    </AppLayout>
</template>
