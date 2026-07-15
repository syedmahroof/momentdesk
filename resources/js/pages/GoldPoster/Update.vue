<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import axios from 'axios';
import { Download, Minus, RefreshCw, TrendingDown, TrendingUp } from 'lucide-vue-next';
import { computed, onMounted, reactive, ref, watch } from 'vue';
import AppLayout from '@/layouts/AppLayout.vue';
import ModalHost from '@/components/ModalHost.vue';
import { useModals } from '@/composables/useModals';
import { type BreadcrumbItem } from '@/types';
import { ensureFonts, loadDocumentAssets, renderDocument, type PosterAssets, type PosterDocument } from './renderer';

const modals = useModals();

interface TemplateSummary { id: number; name: string }
interface RateData { date: string; price_22k_1g: number; price_22k_8g: number; price_18k_1g: number }

const props = defineProps<{
    tenant: unknown;
    templates: TemplateSummary[];
    latestRate: RateData | null;
    rates: RateData[];
}>();

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Update gold rate', href: '/gold-poster/update' }];

const canvasRef = ref<HTMLCanvasElement | null>(null);
const templateId = ref<number | null>(props.templates[0]?.id ?? null);
const doc = ref<PosterDocument | null>(null);
const assets = ref<PosterAssets>({ images: {}, bg: null });
const loading = ref(false);
const busy = ref(false);
const rateTrend = ref<{ direction: string; diff: number; has_previous: boolean } | null>(null);
const statusOverride = ref<string | null>(null);

function todayIso() {
    const d = new Date();
    return `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-${String(d.getDate()).padStart(2, '0')}`;
}

const rate = reactive({
    // Always default to today (local date); prices only fill if a rate exists for the selected date.
    date: todayIso(),
    p22k1g: '',
    p22k8g: '',
    p18k1g: '',
});

// Fill the price fields from the saved rate for the given date, or clear them if none.
function applyRateForDate(date: string) {
    const found = (props.rates ?? []).find((r) => r.date === date);
    rate.p22k1g = found ? String(found.price_22k_1g) : '';
    rate.p22k8g = found ? String(found.price_22k_8g) : '';
    rate.p18k1g = found ? String(found.price_18k_1g) : '';
}

function num(v: string) { return Number(String(v).replace(/[^0-9]/g, '')) || 0; }
function money(v: string) { return `₹${num(v).toLocaleString('en-IN')}`; }
function displayDate(iso: string) {
    try { return new Date(`${iso}T00:00:00`).toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' }); } catch { return iso; }
}
function statusMessage(t: { direction: string; diff: number; has_previous: boolean }) {
    if (!t.has_previous) return "";
    if (t.direction === 'up') return `സ്വർണ വില ഗ്രാമിന് ${t.diff} രൂപ കൂടി`;
    if (t.direction === 'down') return `സ്വർണ വില ഗ്രാമിന് ${t.diff} രൂപ കുറഞ്ഞു`;
    return '';
}

function buildFields(): Record<string, string> {
    const f = { ...(doc.value?.fields ?? {}) };
    if (rate.p22k1g) f['price_22k_1g'] = money(rate.p22k1g);
    if (rate.p22k8g) f['price_22k_8g'] = money(rate.p22k8g);
    if (rate.p18k1g) f['price_18k_1g'] = money(rate.p18k1g);
    f['date'] = displayDate(rate.date);
    if (statusOverride.value != null) f['status'] = statusOverride.value;
    return f;
}

function renderPreview() {
    if (canvasRef.value && doc.value) renderDocument(canvasRef.value, doc.value, assets.value, buildFields());
}

async function loadTemplate(id: number | null) {
    if (!id) { doc.value = null; return; }
    loading.value = true; statusOverride.value = null; rateTrend.value = null;
    try {
        const { data } = await axios.get(`/gold-poster/templates/${id}`);
        doc.value = data.document as PosterDocument;
        assets.value = await loadDocumentAssets(doc.value);
        renderPreview();
    } catch { modals.alert('Could not load that template.', { title: 'Load failed' }); } finally { loading.value = false; }
}

async function updateRate() {
    if (!doc.value) { modals.alert('Select a template first.'); return; }
    if (!rate.p22k1g || !rate.p22k8g || !rate.p18k1g) { modals.alert('Enter all three prices first.'); return; }
    busy.value = true;
    try {
        const { data } = await axios.post('/gold-poster/rates', {
            date: rate.date, price_22k_1g: num(rate.p22k1g), price_22k_8g: num(rate.p22k8g), price_18k_1g: num(rate.p18k1g),
        });
        rateTrend.value = data.trend;
        // Fall back to the template's own status text when there's no message (e.g. first-ever rate).
        statusOverride.value = statusMessage(data.trend) || null;
        renderPreview();
        refetchHistory();
    } catch { modals.alert('Could not save the rate.', { title: 'Save failed' }); } finally { busy.value = false; }
}

function downloadPoster() {
    if (!doc.value) { modals.alert('Select a template first.'); return; }
    const canvas = canvasRef.value; if (!canvas) return;
    renderPreview();
    canvas.toBlob((blob) => {
        if (!blob) return;
        const url = URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url; a.download = `gold-rate-${rate.date}.png`; a.click();
        URL.revokeObjectURL(url);
    }, 'image/png');
}

watch(rate, () => renderPreview());
watch(() => rate.date, (d) => applyRateForDate(d));

onMounted(async () => {
    applyRateForDate(rate.date);
    await ensureFonts();
    await loadTemplate(templateId.value);
    document.fonts.addEventListener('loadingdone', () => renderPreview());
});

const trendText = () => {
    const t = rateTrend.value; if (!t) return '';
    if (!t.has_previous) return '';
    if (t.direction === 'up') return `സ്വർണ വില ഗ്രാമിന് ${t.diff} രൂപ കൂടി`;
    if (t.direction === 'down') return `സ്വർണ വില ഗ്രാമിന് ${t.diff} രൂപ കുറഞ്ഞു`;
    return 'No change vs previous';
};

// ── Rate history: table + chart ──────────────────────────────────────
const history = ref<RateData[]>([...(props.rates ?? [])]);
function fmt(n: number) { return `₹${Number(n).toLocaleString('en-IN')}`; }
function shortDate(iso: string) { try { return new Date(`${iso}T00:00:00`).toLocaleDateString('en-GB', { day: '2-digit', month: 'short' }); } catch { return iso; } }

async function refetchHistory() {
    try { const { data } = await axios.get('/gold-poster/rates'); history.value = data.rates ?? []; } catch { /* ignore */ }
}

const tableRows = computed(() =>
    history.value
        .map((r, i) => ({ ...r, change: i > 0 ? r.price_22k_1g - history.value[i - 1].price_22k_1g : null }))
        .slice()
        .reverse(),
);

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

const inputClass = 'w-full rounded-md border border-input bg-background px-3 py-2 text-sm text-foreground placeholder:text-muted-foreground transition focus:border-ring focus:outline-none focus:ring-2 focus:ring-ring/25';
</script>

<template>
    <Head title="Update gold rate" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="mx-auto flex w-full max-w-7xl flex-col gap-6 p-6 lg:p-8">
            <div>
                <h1 class="text-2xl font-semibold tracking-tight text-foreground">Update gold rate</h1>
                <p class="text-sm text-muted-foreground">Enter today's prices, preview the poster, and download it in one step.</p>
            </div>

            <!-- No templates -->
            <div v-if="!templates.length" class="rounded-lg border border-dashed border-border bg-card px-6 py-14 text-center">
                <p class="text-sm font-medium text-foreground">No saved poster designs yet</p>
                <p class="mx-auto mt-1 mb-5 max-w-sm text-sm text-muted-foreground">Design a poster and save it as a template first — then come here to update prices daily.</p>
                <Link href="/gold-poster" class="inline-flex items-center gap-2 rounded-md bg-primary px-4 py-2.5 text-sm font-medium text-primary-foreground shadow-xs transition hover:bg-primary/90">Open the poster editor</Link>
            </div>

            <div v-else class="grid gap-6 xl:grid-cols-[minmax(0,300px)_minmax(0,1fr)_minmax(0,440px)]">
                <!-- Controls -->
                <div class="space-y-5 xl:order-1">
                    <section class="rounded-lg border border-border bg-card p-5 shadow-xs">
                        <label class="mb-1.5 block text-sm font-medium text-foreground">Design</label>
                        <select v-model="templateId" class="h-10 w-full rounded-md border border-input bg-background px-2 text-sm text-foreground" @change="loadTemplate(templateId)">
                            <option v-for="t in templates" :key="t.id" :value="t.id">{{ t.name }}</option>
                        </select>
                    </section>

                    <section class="rounded-lg border border-border bg-card p-5 shadow-xs">
                        <h2 class="mb-3 text-sm font-semibold text-foreground">Today's rate</h2>
                        <div class="grid gap-3 sm:grid-cols-2">
                            <div><label class="mb-1.5 block text-xs font-medium text-muted-foreground">Date</label><input v-model="rate.date" type="date" :class="inputClass" /></div>
                            <div><label class="mb-1.5 block text-xs font-medium text-muted-foreground">22K · 1g</label><input v-model="rate.p22k1g" inputmode="numeric" placeholder="13880" :class="inputClass" /></div>
                            <div><label class="mb-1.5 block text-xs font-medium text-muted-foreground">22K · 8g</label><input v-model="rate.p22k8g" inputmode="numeric" placeholder="111040" :class="inputClass" /></div>
                            <div><label class="mb-1.5 block text-xs font-medium text-muted-foreground">18K · 1g</label><input v-model="rate.p18k1g" inputmode="numeric" placeholder="11356" :class="inputClass" /></div>
                        </div>
                        <p class="mt-3 text-xs text-muted-foreground">The status line auto-updates from the 1g 22K change vs the previous saved date.</p>
                        <div v-if="rateTrend" class="mt-3">
                            <span :class="['inline-flex items-center gap-1 text-sm font-medium', rateTrend.direction === 'up' ? 'text-emerald-600 dark:text-emerald-400' : rateTrend.direction === 'down' ? 'text-rose-600 dark:text-rose-400' : 'text-muted-foreground']">
                                <component :is="rateTrend.direction === 'up' ? TrendingUp : rateTrend.direction === 'down' ? TrendingDown : Minus" class="h-4 w-4" />
                                {{ trendText() }}
                            </span>
                        </div>
                    </section>

                    <button type="button" :disabled="busy || loading" class="inline-flex w-full items-center justify-center gap-2 rounded-md bg-primary px-4 py-3 text-sm font-medium text-primary-foreground shadow-xs transition hover:bg-primary/90 disabled:opacity-60" @click="updateRate">
                        <RefreshCw class="h-4 w-4" /> {{ busy ? 'Saving…' : 'Update rate' }}
                    </button>
                </div>

                <!-- Preview -->
                <div class="xl:order-3 xl:sticky xl:top-6 xl:self-start">
                    <div class="rounded-lg border border-border bg-card p-4 shadow-xs">
                        <div class="relative overflow-hidden rounded-md border border-border bg-muted">
                            <canvas ref="canvasRef" class="block h-auto w-full" />
                            <div v-if="loading" class="absolute inset-0 flex items-center justify-center bg-background/60 text-sm text-muted-foreground">Loading…</div>
                        </div>
                        <button type="button" :disabled="loading" class="mt-3 inline-flex w-full items-center justify-center gap-2 rounded-md bg-primary px-4 py-2.5 text-sm font-medium text-primary-foreground shadow-xs transition hover:bg-primary/90 disabled:opacity-60" @click="downloadPoster">
                            <Download class="h-4 w-4" /> Download
                        </button>
                    </div>
                </div>

                <!-- Analytics (middle) -->
                <div class="space-y-6 xl:order-2">
                <!-- Trend chart -->
                <section v-if="history.length" class="rounded-lg border border-border bg-card p-5 shadow-xs">
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
            <section v-if="history.length" class="overflow-hidden rounded-lg border border-border bg-card shadow-xs">
                <div class="flex items-center justify-between border-b border-border px-5 py-4">
                    <h2 class="text-sm font-semibold text-foreground">Rate history</h2>
                    <span class="text-xs text-muted-foreground">{{ history.length }} day{{ history.length === 1 ? '' : 's' }}</span>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm">
                        <thead>
                            <tr class="border-b border-border bg-muted/40 text-xs uppercase tracking-wide text-muted-foreground">
                                <th class="px-5 py-3 font-medium">Date</th>
                                <th class="px-5 py-3 text-right font-medium">22K · 1g</th>
                                <th class="px-5 py-3 text-right font-medium">22K · 8g</th>
                                <th class="px-5 py-3 text-right font-medium">18K · 1g</th>
                                <th class="px-5 py-3 text-right font-medium">Change (1g 22K)</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-border">
                            <tr v-for="r in tableRows" :key="r.date" class="transition hover:bg-muted/40">
                                <td class="px-5 py-2.5 text-foreground">{{ displayDate(r.date) }}</td>
                                <td class="px-5 py-2.5 text-right font-medium tabular-nums text-foreground">{{ fmt(r.price_22k_1g) }}</td>
                                <td class="px-5 py-2.5 text-right tabular-nums text-muted-foreground">{{ fmt(r.price_22k_8g) }}</td>
                                <td class="px-5 py-2.5 text-right tabular-nums text-muted-foreground">{{ fmt(r.price_18k_1g) }}</td>
                                <td class="px-5 py-2.5 text-right tabular-nums">
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
                </div>
            </div>
        </div>

        <ModalHost />
    </AppLayout>
</template>
